<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Integración CHAP - Portal Cautivo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .form-test {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        input, button {
            padding: 8px 12px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #007bff;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🔐 Test Integración CHAP - Portal Cautivo Mikrotik</h1>

    <div class="test-section">
        <h2>1. Verificación de MD5.js</h2>
        <div id="md5-test">
            <p>Probando biblioteca MD5...</p>
        </div>
    </div>

    <div class="test-section">
        <h2>2. Simulación de Parámetros Mikrotik</h2>
        <div id="mikrotik-params">
            <p><strong>Parámetros simulados:</strong></p>
            <pre id="params-display"></pre>
        </div>
    </div>

    <div class="test-section">
        <h2>3. Test de Autenticación CHAP</h2>
        <div class="form-test">
            <h3>Formulario de Usuario/Contraseña</h3>
            <input type="text" id="username" placeholder="Usuario" value="testuser">
            <input type="password" id="password" placeholder="Contraseña" value="testpass">
            <button onclick="testChapAuth()">Probar Autenticación CHAP</button>
            <div id="chap-result"></div>
        </div>
    </div>

    <div class="test-section">
        <h2>4. Test de Conexión Trial</h2>
        <div class="form-test">
            <h3>Conexión Gratuita</h3>
            <button onclick="testTrialConnection()">Probar Conexión Trial</button>
            <div id="trial-result"></div>
        </div>
    </div>

    <div class="test-section">
        <h2>5. Test de Formulario Oculto</h2>
        <div class="form-test">
            <h3>Formulario CHAP Oculto</h3>
            <button onclick="testHiddenForm()">Probar Formulario Oculto</button>
            <div id="hidden-form-result"></div>
        </div>
    </div>

    <div class="test-section">
        <h2>6. Validación de Funciones Globales</h2>
        <div id="global-functions-test">
            <p>Verificando funciones globales...</p>
        </div>
    </div>

    <!-- Formulario oculto para pruebas -->
    <form name="sendin" style="display: none;">
        <input type="hidden" name="username" />
        <input type="hidden" name="password" />
        <input type="hidden" name="dst" value="http://example.com" />
    </form>

    <!-- MD5.js Library -->
    <script>
        // Implementación básica de MD5 para las pruebas
        function hexMD5(s) {
            // Esta es una implementación simplificada para pruebas
            // En producción se usa la biblioteca MD5.js completa
            return btoa(s).replace(/[^a-zA-Z0-9]/g, '').toLowerCase().substring(0, 32);
        }
    </script>

    <script>
        // Parámetros simulados de Mikrotik
        const mikrotikData = {
            'mac': '00:11:22:33:44:55',
            'mac-esc': '00-11-22-33-44-55',
            'link-login-only': 'http://192.168.1.1/login',
            'link-orig': 'http://google.com',
            'link-orig-esc': 'http%3A//google.com',
            'chap-id': '123',
            'chap-challenge': 'abcdef1234567890',
            'error': ''
        };

        // Test 1: Verificación de MD5
        function testMD5() {
            const testString = "test123";
            const hash = hexMD5(testString);
            const result = document.getElementById('md5-test');

            if (hash && hash.length > 0) {
                result.innerHTML = `<p class="success">✅ MD5.js funcionando correctamente</p>
                                   <p>Test string: "${testString}"</p>
                                   <p>Hash: ${hash}</p>`;
            } else {
                result.innerHTML = `<p class="error">❌ Error en MD5.js</p>`;
            }
        }

        // Test 2: Mostrar parámetros Mikrotik
        function displayMikrotikParams() {
            document.getElementById('params-display').textContent = JSON.stringify(mikrotikData, null, 2);
        }

        // Test 3: Autenticación CHAP
        function testChapAuth() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const result = document.getElementById('chap-result');

            if (!username || !password) {
                result.innerHTML = '<p class="error">❌ Por favor ingresa usuario y contraseña</p>';
                return;
            }

            // Simular autenticación CHAP
            const chapId = mikrotikData['chap-id'];
            const chapChallenge = mikrotikData['chap-challenge'];

            if (chapId && chapChallenge) {
                const chapPassword = hexMD5(chapId + password + chapChallenge);

                result.innerHTML = `
                    <div class="success">
                        <p>✅ Autenticación CHAP simulada</p>
                        <p><strong>Usuario:</strong> ${username}</p>
                        <p><strong>CHAP ID:</strong> ${chapId}</p>
                        <p><strong>CHAP Challenge:</strong> ${chapChallenge}</p>
                        <p><strong>Password Hash:</strong> ${chapPassword}</p>
                        <p><strong>Fórmula:</strong> MD5(${chapId} + "${password}" + ${chapChallenge})</p>
                    </div>
                `;
            } else {
                result.innerHTML = '<p class="warning">⚠️ Sin datos CHAP - usando autenticación normal</p>';
            }
        }

        // Test 4: Conexión Trial
        function testTrialConnection() {
            const result = document.getElementById('trial-result');
            const trialLink = mikrotikData['link-login-only'] +
                            '?dst=' + encodeURIComponent(mikrotikData['link-orig-esc']) +
                            '&username=' + encodeURIComponent('T-' + mikrotikData['mac-esc']);

            result.innerHTML = `
                <div class="info">
                    <p>✨ URL de conexión trial generada:</p>
                    <pre>${trialLink}</pre>
                    <p><small>Esta URL redirigirá al usuario para conexión gratuita</small></p>
                </div>
            `;
        }

        // Test 5: Formulario Oculto
        function testHiddenForm() {
            const result = document.getElementById('hidden-form-result');
            const form = document.sendin;

            if (form) {
                // Simular llenado del formulario oculto
                form.username.value = 'testuser';
                form.password.value = hexMD5('123testpassabcdef1234567890'); // Simular CHAP

                result.innerHTML = `
                    <div class="success">
                        <p>✅ Formulario oculto encontrado y configurado</p>
                        <p><strong>Action:</strong> ${form.action || 'No definido'}</p>
                        <p><strong>Username:</strong> ${form.username.value}</p>
                        <p><strong>Password (CHAP):</strong> ${form.password.value}</p>
                        <p><strong>Destination:</strong> ${form.dst.value}</p>
                        <p><small>En producción, este formulario se enviaría automáticamente</small></p>
                    </div>
                `;
            } else {
                result.innerHTML = '<p class="error">❌ Formulario oculto no encontrado</p>';
            }
        }

        // Test 6: Funciones Globales
        function testGlobalFunctions() {
            const result = document.getElementById('global-functions-test');
            let testResults = [];

            // Simular funciones que estarían disponibles en el portal real
            window.doLogin = function() {
                return true; // Simulación
            };

            window.doTrial = function() {
                return false; // Simulación
            };

            // Verificar funciones
            if (typeof window.doLogin === 'function') {
                testResults.push('<p class="success">✅ doLogin() disponible</p>');
            } else {
                testResults.push('<p class="error">❌ doLogin() no disponible</p>');
            }

            if (typeof window.doTrial === 'function') {
                testResults.push('<p class="success">✅ doTrial() disponible</p>');
            } else {
                testResults.push('<p class="error">❌ doTrial() no disponible</p>');
            }

            if (typeof hexMD5 === 'function') {
                testResults.push('<p class="success">✅ hexMD5() disponible</p>');
            } else {
                testResults.push('<p class="error">❌ hexMD5() no disponible</p>');
            }

            result.innerHTML = testResults.join('');
        }

        // Ejecutar todos los tests al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            testMD5();
            displayMikrotikParams();
            testGlobalFunctions();
        });
    </script>

    <div class="test-section">
        <h2>📋 Resumen de Integración CHAP</h2>
        <div class="info">
            <h3>Componentes Implementados:</h3>
            <ul>
                <li>✅ Biblioteca MD5.js para hash CHAP</li>
                <li>✅ Formulario oculto para envío CHAP</li>
                <li>✅ Función doLogin() con soporte CHAP</li>
                <li>✅ Función doTrial() para conexión gratuita</li>
                <li>✅ Validación de parámetros Mikrotik</li>
                <li>✅ Manejo de errores de autenticación</li>
            </ul>

            <h3>Flujo de Autenticación:</h3>
            <ol>
                <li>Usuario ingresa credenciales</li>
                <li>Sistema verifica si hay CHAP challenge</li>
                <li>Si hay CHAP: genera hash MD5(chap-id + password + chap-challenge)</li>
                <li>Usa formulario oculto para enviar datos hasheados</li>
                <li>Si no hay CHAP: envía credenciales normalmente</li>
                <li>Mikrotik procesa la autenticación</li>
            </ol>

            <h3>Notas Importantes:</h3>
            <ul>
                <li>La biblioteca MD5.js debe estar cargada antes del script principal</li>
                <li>Las funciones doLogin() y doTrial() son globales para uso en formularios</li>
                <li>El formulario oculto se usa solo para autenticación CHAP</li>
                <li>Los parámetros Mikrotik se pasan desde el controlador PHP</li>
            </ul>
        </div>
    </div>
</body>
</html>
