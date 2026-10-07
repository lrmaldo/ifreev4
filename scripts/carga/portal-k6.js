// Prueba de carga del portal cautivo con k6 (https://k6.io).
//
// Simula dispositivos que llegan al portal desde el MikroTik: abren el portal,
// registran métricas y (opcional) llenan el formulario. Cada iteración es un
// dispositivo nuevo con una MAC distinta.
//
// Uso (usar una zona de PRUEBA, porque se crean métricas y registros reales):
//   k6 run -e BASE_URL=https://v3.i-free.com.mx -e ZONA=prueba-carga scripts/carga/portal-k6.js
//   k6 run -e BASE_URL=... -e ZONA=... -e FORMULARIO=1 -e PICO=400 scripts/carga/portal-k6.js
//
// Variables:
//   BASE_URL    URL del sistema (sin / al final)
//   ZONA        id o id_personalizado de la zona de prueba
//   FORMULARIO  1 = también envía el formulario
//   RESPUESTAS  JSON con los campos del formulario de la zona (usa el nombre interno de cada campo).
//               Default: {"nombre":"Prueba"}. Ej: -e RESPUESTAS='{"nombre":"Prueba","telefono":"5512345678"}'
//   PICO        usuarios virtuales simultáneos en el pico (default 200)

import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const ZONA = __ENV.ZONA || '1';
const FORMULARIO = __ENV.FORMULARIO === '1';
const PICO = parseInt(__ENV.PICO || '200', 10);
const RESPUESTAS = JSON.parse(__ENV.RESPUESTAS || '{"nombre":"Prueba"}');
let erroresMostrados = 0;

export const options = {
    stages: [
        { duration: '1m', target: Math.round(PICO / 4) }, // llegan los primeros
        { duration: '2m', target: PICO },                 // apertura de puertas
        { duration: '5m', target: PICO },                 // pico sostenido
        { duration: '1m', target: 0 },
    ],
    thresholds: {
        http_req_failed: ['rate<0.01'],     // menos de 1% de errores
        http_req_duration: ['p(95)<1500'],  // 95% de respuestas en menos de 1.5 s
        'checks{paso:portal}': ['rate>0.99'],
    },
};

function macAleatoria() {
    const hex = () => Math.floor(Math.random() * 256).toString(16).padStart(2, '0').toUpperCase();
    return ['02', hex(), hex(), hex(), hex(), hex()].join(':');
}

export default function () {
    const mac = macAleatoria();

    // 1. El MikroTik manda al usuario al portal (login.html hace este POST)
    const portal = http.post(`${BASE_URL}/login_formulario/${ZONA}`, {
        mac: mac,
        'link-login-only': 'http://10.5.50.1/login',
        'link-orig': 'http://example.com/',
    }, { tags: { paso: 'portal' } });

    const token = (portal.body || '').match(/window\.PORTAL_TOKEN = "([^"]+)"/);
    check(portal, {
        'portal responde 200': (r) => r.status === 200,
        'portal entrega token': () => token !== null,
    }, { paso: 'portal' });

    if (!token) {
        return;
    }

    const params = {
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Portal-Token': token[1] },
    };

    sleep(1 + Math.random() * 2);

    // 2. El JavaScript del portal registra la visita
    const track = http.post(`${BASE_URL}/hotspot-metrics/track`, JSON.stringify({
        tipo_visual: 'carrusel',
        dispositivo: 'k6',
        navegador: 'k6',
        user_agent: 'k6-carga',
    }), Object.assign({ tags: { paso: 'track' } }, params));
    check(track, { 'track responde 200': (r) => r.status === 200 });

    // 3. Formulario (opcional)
    if (FORMULARIO) {
        sleep(5 + Math.random() * 10); // tiempo que tarda una persona en llenarlo
        const form = http.post(`${BASE_URL}/zona/formulario/responder`, JSON.stringify({
            respuestas: RESPUESTAS,
            acepta_privacidad: 1,
        }), Object.assign({ tags: { paso: 'formulario' } }, params));
        const formOk = check(form, { 'formulario responde 200': (r) => r.status === 200 });
        // Muestra las primeras respuestas fallidas para saber por qué (p. ej. 422 = falta un campo obligatorio)
        if (!formOk && erroresMostrados < 3) {
            erroresMostrados++;
            console.warn(`formulario HTTP ${form.status}: ${String(form.body).slice(0, 300)}`);
        }
    }

    // 4. Al terminar de ver la campaña se actualiza la duración
    sleep(5 + Math.random() * 10);
    const update = http.post(`${BASE_URL}/hotspot-metrics/update`, JSON.stringify({
        duracion_visual: 15,
        tipo_visual: 'carrusel',
    }), Object.assign({ tags: { paso: 'update' } }, params));
    check(update, { 'update responde 200': (r) => r.status === 200 });
}
