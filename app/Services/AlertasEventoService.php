<?php

namespace App\Services;

use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\MetricaDetalle;
use App\Models\TelegramChat;
use App\Models\Zona;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Revisiones que corren cada minuto (evento:vigilar) para avisar por Telegram
 * si algo falla durante un evento, antes de que lo note la gente en la puerta.
 *
 * Cada problema avisa una sola vez (y vuelve a recordar cada N minutos si sigue);
 * cuando se resuelve, manda un aviso de "resuelto".
 */
class AlertasEventoService
{
    private const CLAVE_ERRORES = 'ifree:errores:';
    private const CLAVE_ACTIVA = 'ifree:alerta-activa:';
    private const CLAVE_RECORDADA = 'ifree:alerta-recordada:';
    private const CLAVE_SEGUIDAS = 'ifree:alerta-seguidas:';

    /**
     * Lo llama el manejador de excepciones: cuenta errores por minuto.
     */
    public static function contarError(): void
    {
        try {
            $clave = self::CLAVE_ERRORES . now()->format('YmdHi');
            Cache::add($clave, 0, now()->addMinutes(15));
            Cache::increment($clave);
        } catch (\Throwable) {
            // Si la caché falla no hay que empeorar el error original
        }
    }

    /**
     * Ejecuta todas las revisiones y devuelve los mensajes a enviar.
     *
     * @return array<int, string>
     */
    public function revisar(): array
    {
        $estados = [
            ...$this->revisarPortal(),
            $this->revisarErrores(),
        ];

        foreach (Zona::where('portal_tema', 'evento')->get() as $zona) {
            $estados[] = $this->revisarVisitas($zona);
            $estados[] = $this->revisarRegistros($zona);
        }

        $mensajes = [];
        foreach ($estados as $estado) {
            [$clave, $problema, $resuelto] = $estado;
            if ($mensaje = $this->transicion($clave, $problema, $resuelto, $estado[3] ?? 1)) {
                $mensajes[] = $mensaje;
            }
        }

        return $mensajes;
    }

    /**
     * Chats a los que se envían las alertas: los de IFREE_ALERTAS_CHATS o, si no hay,
     * los chats activos de las zonas con tema de evento.
     *
     * @return array<int, string>
     */
    public function destinatarios(): array
    {
        $configurados = array_values(array_filter(array_map('trim', explode(',', (string) config('ifree.alertas.chats')))));
        if ($configurados) {
            return $configurados;
        }

        return TelegramChat::where('activo', true)
            ->whereHas('zonas', fn ($q) => $q->where('portal_tema', 'evento'))
            ->pluck('chat_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * El portal responde desde fuera (DNS + HTTPS + PHP + base de datos) y a tiempo.
     */
    private function revisarPortal(): array
    {
        $url = config('ifree.portal_url') . '/portal/ping';
        $lento = (int) config('ifree.alertas.portal_lento_ms');
        $inicio = microtime(true);

        try {
            $respuesta = Http::timeout(10)->connectTimeout(5)->get($url);
            $ms = (int) round((microtime(true) - $inicio) * 1000);
            $caido = $respuesta->failed() ? "respondió HTTP {$respuesta->status()}" : null;
        } catch (\Throwable $e) {
            $ms = null;
            $caido = 'no respondió (' . class_basename($e) . ')';
        }

        return [
            // Dos revisiones seguidas (~2 min): un tropiezo de un minuto (deploy, reinicio de PHP) no alerta
            ['portal-caido', $caido ? "🔴 <b>El servidor de i-Free no responde</b> (todas las zonas)\n{$url} {$caido}.\nLos MikroTik con el login.html nuevo conectan directo con el trial: hay internet, pero no se cuentan visitas ni registros." : null,
                "✅ <b>El servidor de i-Free volvió a responder</b> (todas las zonas)", 2],
            ['portal-lento', !$caido && $ms > $lento ? "🟠 <b>El servidor de i-Free está lento</b> (todas las zonas)\nTardó {$ms} ms en responder (límite {$lento} ms)." : null,
                "✅ <b>El servidor de i-Free ya responde rápido</b>" . ($ms ? " ({$ms} ms)" : ''), 2],
        ];
    }

    /**
     * Muchos errores de la aplicación en los últimos 5 minutos.
     */
    private function revisarErrores(): array
    {
        $total = 0;
        for ($i = 0; $i < 5; $i++) {
            $total += (int) Cache::get(self::CLAVE_ERRORES . now()->subMinutes($i)->format('YmdHi'), 0);
        }
        $limite = (int) config('ifree.alertas.errores_5min');

        return ['errores', $total >= $limite ? "🔴 <b>{$total} errores en 5 minutos</b>\nRevisa storage/logs/laravel.log en el servidor." : null,
            '✅ <b>Los errores bajaron</b> a la normalidad'];
    }

    /**
     * Había visitas y de pronto dejaron de llegar: AP, MikroTik o internet del evento.
     */
    private function revisarVisitas(Zona $zona): array
    {
        $minutos = (int) config('ifree.alertas.sin_visitas_min');
        $visitas = fn ($desde, $hasta) => MetricaDetalle::where('tipo_evento', 'vista')
            ->whereIn('metrica_id', HotspotMetric::select('id')->where('zona_id', $zona->id))
            ->whereBetween('fecha_hora', [$desde, $hasta])
            ->count();

        $antes = $visitas(now()->subMinutes($minutos + 60), now()->subMinutes($minutos));
        $ahora = $visitas(now()->subMinutes($minutos), now());

        return ["sin-visitas-{$zona->id}",
            $antes >= (int) config('ifree.alertas.sin_visitas_previas') && $ahora === 0
                ? "🟠 <b>{$this->e($zona->nombre)}: sin visitas</b>\nNo llega nadie al portal desde hace {$minutos} min (la hora anterior hubo {$antes}). Revisa APs, MikroTik o el internet del evento."
                : null,
            "✅ <b>{$this->e($zona->nombre)}</b>: vuelven a llegar visitas"];
    }

    /**
     * Se conectan dispositivos nuevos pero nadie termina el formulario.
     */
    private function revisarRegistros(Zona $zona): array
    {
        if ($zona->tipo_registro !== 'formulario') {
            return ["sin-registros-{$zona->id}", null, ''];
        }

        $minutos = (int) config('ifree.alertas.sin_registros_min');
        $desde = now()->subMinutes($minutos);
        $nuevos = HotspotMetric::where('zona_id', $zona->id)->where('created_at', '>=', $desde)->count();
        $registros = FormResponse::where('zona_id', $zona->id)->where('created_at', '>=', $desde)->count();

        return ["sin-registros-{$zona->id}",
            $nuevos >= (int) config('ifree.alertas.sin_registros_nuevos') && $registros === 0
                ? "🟠 <b>{$this->e($zona->nombre)}: nadie se registra</b>\n{$nuevos} dispositivos nuevos en {$minutos} min y 0 registros. Revisa el formulario del portal."
                : null,
            "✅ <b>{$this->e($zona->nombre)}</b>: vuelven a llegar registros"];
    }

    /**
     * Decide si hay que avisar: al aparecer el problema, como recordatorio si sigue,
     * o cuando se resuelve.
     */
    private function transicion(string $clave, ?string $problema, string $resuelto, int $seguidas = 1): ?string
    {
        $activa = Cache::get(self::CLAVE_ACTIVA . $clave);

        if ($problema) {
            // Algunos problemas deben repetirse N revisiones seguidas antes de avisar
            Cache::add(self::CLAVE_SEGUIDAS . $clave, 0, now()->addMinutes(30));
            if (Cache::increment(self::CLAVE_SEGUIDAS . $clave) < $seguidas) {
                return null;
            }

            if (!$activa) {
                // La hora de inicio no se pisa: con ella se calcula cuánto duró
                Cache::forever(self::CLAVE_ACTIVA . $clave, now()->toIso8601String());
            }
            $recordar = now()->addMinutes((int) config('ifree.alertas.recordar_min'));

            if (!$activa) {
                Cache::put(self::CLAVE_RECORDADA . $clave, true, $recordar);

                return $problema;
            }

            return Cache::add(self::CLAVE_RECORDADA . $clave, true, $recordar) ? $problema . "\n<i>(sigue pasando)</i>" : null;
        }

        Cache::forget(self::CLAVE_SEGUIDAS . $clave);

        if ($activa) {
            Cache::forget(self::CLAVE_ACTIVA . $clave);
            Cache::forget(self::CLAVE_RECORDADA . $clave);
            $minutos = max(1, (int) round(\Carbon\Carbon::parse($activa)->diffInMinutes(now())));

            return $resuelto . "\nDuró unos {$minutos} min.";
        }

        return null;
    }

    private function e(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES);
    }
}
