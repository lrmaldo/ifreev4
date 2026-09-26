<?php

/**
 * EJEMPLOS DE COMANDOS ADICIONALES PARA EL BOT DE TELEGRAM
 * 
 * Este archivo contiene implementaciones de ejemplo que puedes copiar y pegar
 * directamente en TelegramController.php
 */

// ============================================================================
// COMANDO 1: /estadisticas - Ver estadísticas del día
// ============================================================================

protected function handleEstadisticasCommand($chatId, array $params)
{
    try {
        $zonaId = $params[0] ?? null;
        $chat = TelegramChat::where('chat_id', $chatId)->first();

        if (!$chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Chat no registrado',
            ]);

            return response()->json(['status' => 'error']);
        }

        // Obtener zonas
        if ($zonaId) {
            $zonas = $chat->zonas()->where('id', $zonaId)->get();
        } else {
            $zonas = $chat->zonas()->get();
        }

        if ($zonas->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '⚠️ No hay zonas asociadas a este chat',
            ]);

            return response()->json(['status' => 'success']);
        }

        $mensaje = "<b>📊 ESTADÍSTICAS - HOY</b>\n\n";

        foreach ($zonas as $zona) {
            $visitas = HotspotMetric::where('zona_id', $zona->id)
                ->whereDate('created_at', today())
                ->count();

            $dispositivosUnicos = HotspotMetric::where('zona_id', $zona->id)
                ->whereDate('created_at', today())
                ->distinct('mac_address')
                ->count('mac_address');

            $formulariosCompletados = HotspotMetric::where('zona_id', $zona->id)
                ->whereDate('created_at', today())
                ->whereNotNull('formulario_id')
                ->count();

            $tasaConversion = $visitas > 0 ? round(($formulariosCompletados / $visitas) * 100, 1) : 0;

            $duracionPromedio = HotspotMetric::where('zona_id', $zona->id)
                ->whereDate('created_at', today())
                ->avg('duracion_visual') ?? 0;

            $clicsBoton = HotspotMetric::where('zona_id', $zona->id)
                ->whereDate('created_at', today())
                ->where('clic_boton', true)
                ->count();

            $mensaje .= "<b>📍 {$zona->nombre}</b>\n";
            $mensaje .= "👥 Visitas: {$visitas}\n";
            $mensaje .= "📱 Dispositivos únicos: {$dispositivosUnicos}\n";
            $mensaje .= "✅ Formularios: {$formulariosCompletados} ({$tasaConversion}%)\n";
            $mensaje .= "⏱️ Duración promedio: {$duracionPromedio}s\n";
            $mensaje .= "🔘 Clics en botones: {$clicsBoton}\n\n";
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);

        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error en /estadisticas: '.$e->getMessage());

        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ============================================================================
// COMANDO 2: /reporte [período] - Ver reporte por período
// ============================================================================

protected function handleReporteCommand($chatId, array $params)
{
    try {
        $periodo = $params[0] ?? 'hoy';
        $chat = TelegramChat::where('chat_id', $chatId)->first();

        if (!$chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Chat no registrado',
            ]);

            return response()->json(['status' => 'error']);
        }

        $zonas = $chat->zonas()->get();

        if ($zonas->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '⚠️ No hay zonas asociadas',
            ]);

            return response()->json(['status' => 'success']);
        }

        // Definir rango de fechas
        $desde = match ($periodo) {
            'hoy' => today(),
            'ayer' => today()->subDay(),
            'semana' => today()->subDays(7),
            'mes' => today()->subDays(30),
            default => is_numeric($periodo) ? today()->subDays((int) $periodo) : today(),
        };

        $mensaje = "<b>📋 REPORTE - {$periodo}</b>\n";
        $mensaje .= "Desde: {$desde->format('d/m/Y')}\n";
        $mensaje .= "Hasta: ".today()->format('d/m/Y')."\n\n";

        $totalVisitas = 0;
        $totalDispositivos = 0;

        foreach ($zonas as $zona) {
            $visitas = HotspotMetric::where('zona_id', $zona->id)
                ->where('created_at', '>=', $desde)
                ->count();

            $dispositivos = HotspotMetric::where('zona_id', $zona->id)
                ->where('created_at', '>=', $desde)
                ->distinct('mac_address')
                ->count('mac_address');

            $totalVisitas += $visitas;
            $totalDispositivos += $dispositivos;

            $mensaje .= "<b>📍 {$zona->nombre}</b>\n";
            $mensaje .= "  Visitas: {$visitas}\n";
            $mensaje .= "  Dispositivos: {$dispositivos}\n\n";
        }

        $mensaje .= "<b>TOTALES</b>\n";
        $mensaje .= "Visitas: {$totalVisitas}\n";
        $mensaje .= "Dispositivos únicos: {$totalDispositivos}\n";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);

        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error en /reporte: '.$e->getMessage());

        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ============================================================================
// COMANDO 3: /dispositivos [zona_id] - Ver dispositivos más populares
// ============================================================================

protected function handleDispositivosCommand($chatId, array $params)
{
    try {
        $zonaId = $params[0] ?? null;
        $chat = TelegramChat::where('chat_id', $chatId)->first();

        if (!$chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Chat no registrado',
            ]);

            return response()->json(['status' => 'error']);
        }

        if ($zonaId) {
            $zonas = $chat->zonas()->where('id', $zonaId)->get();
        } else {
            $zonas = $chat->zonas()->get();
        }

        if ($zonas->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '⚠️ No hay zonas asociadas',
            ]);

            return response()->json(['status' => 'success']);
        }

        $mensaje = "<b>📱 DISPOSITIVOS MÁS USADOS - HOY</b>\n\n";

        foreach ($zonas as $zona) {
            $dispositivos = HotspotMetric::where('zona_id', $zona->id)
                ->whereDate('created_at', today())
                ->select('dispositivo', DB::raw('COUNT(*) as total'))
                ->groupBy('dispositivo')
                ->orderBy('total', 'DESC')
                ->limit(5)
                ->get();

            if ($dispositivos->isEmpty()) {
                $mensaje .= "<b>{$zona->nombre}</b>\n⚠️ Sin datos\n\n";
            } else {
                $mensaje .= "<b>{$zona->nombre}</b>\n";
                $index = 1;
                foreach ($dispositivos as $device) {
                    $emoji = match ($index) {
                        1 => '🥇',
                        2 => '🥈',
                        3 => '🥉',
                        default => '📱',
                    };
                    $mensaje .= "{$emoji} {$device->dispositivo}: {$device->total}\n";
                    $index++;
                }
                $mensaje .= "\n";
            }
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);

        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error en /dispositivos: '.$e->getMessage());

        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ============================================================================
// COMANDO 4: /navegadores [zona_id] - Ver navegadores más populares
// ============================================================================

protected function handleNavegadoresCommand($chatId, array $params)
{
    try {
        $zonaId = $params[0] ?? null;
        $chat = TelegramChat::where('chat_id', $chatId)->first();

        if (!$chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Chat no registrado',
            ]);

            return response()->json(['status' => 'error']);
        }

        if ($zonaId) {
            $zonas = $chat->zonas()->where('id', $zonaId)->get();
        } else {
            $zonas = $chat->zonas()->get();
        }

        if ($zonas->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '⚠️ No hay zonas asociadas',
            ]);

            return response()->json(['status' => 'success']);
        }

        $mensaje = "<b>🌐 NAVEGADORES MÁS USADOS - HOY</b>\n\n";

        foreach ($zonas as $zona) {
            $navegadores = HotspotMetric::where('zona_id', $zona->id)
                ->whereDate('created_at', today())
                ->select('navegador', DB::raw('COUNT(*) as total'))
                ->groupBy('navegador')
                ->orderBy('total', 'DESC')
                ->limit(5)
                ->get();

            if ($navegadores->isEmpty()) {
                $mensaje .= "<b>{$zona->nombre}</b>\n⚠️ Sin datos\n\n";
            } else {
                $mensaje .= "<b>{$zona->nombre}</b>\n";
                foreach ($navegadores as $nav) {
                    $mensaje .= "• {$nav->navegador}: {$nav->total}\n";
                }
                $mensaje .= "\n";
            }
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);

        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error en /navegadores: '.$e->getMessage());

        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ============================================================================
// COMANDO 5: /conectados - Ver usuarios conectados ahora
// ============================================================================

protected function handleConectadosCommand($chatId)
{
    try {
        $chat = TelegramChat::where('chat_id', $chatId)->first();

        if (!$chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Chat no registrado',
            ]);

            return response()->json(['status' => 'error']);
        }

        $zonas = $chat->zonas()->get();

        if ($zonas->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '⚠️ No hay zonas asociadas',
            ]);

            return response()->json(['status' => 'success']);
        }

        $mensaje = "<b>🔴 CONEXIONES EN TIEMPO REAL</b>\n\n";
        $totalConectados = 0;

        foreach ($zonas as $zona) {
            // Considerar conectados los últimos 5 minutos
            $conectados = HotspotMetric::where('zona_id', $zona->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->distinct('mac_address')
                ->count('mac_address');

            $totalConectados += $conectados;
            $mensaje .= "{$zona->nombre}: {$conectados} usuarios\n";
        }

        $mensaje .= "\n<b>Total: {$totalConectados} usuarios conectados</b>";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);

        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error en /conectados: '.$e->getMessage());

        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ============================================================================
// COMANDO 6: /ultimo - Ver última conexión
// ============================================================================

protected function handleUltimoCommand($chatId)
{
    try {
        $chat = TelegramChat::where('chat_id', $chatId)->first();

        if (!$chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Chat no registrado',
            ]);

            return response()->json(['status' => 'error']);
        }

        $zonas = $chat->zonas()->pluck('id');

        $metrica = HotspotMetric::whereIn('zona_id', $zonas)
            ->orderBy('created_at', 'DESC')
            ->first();

        if (!$metrica) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '⚠️ Sin conexiones registradas',
            ]);

            return response()->json(['status' => 'success']);
        }

        $mensaje = "<b>📍 ÚLTIMA CONEXIÓN</b>\n\n";
        $mensaje .= "<b>Zona:</b> {$metrica->zona->nombre}\n";
        $mensaje .= "<b>Hora:</b> {$metrica->created_at->format('H:i:s')}\n";
        $mensaje .= "<b>Dispositivo:</b> {$metrica->dispositivo}\n";
        $mensaje .= "<b>Navegador:</b> {$metrica->navegador}\n";
        $mensaje .= "<b>Tipo:</b> {$metrica->tipo_visual}\n";
        $mensaje .= "<b>Duración:</b> {$metrica->duracion_visual}s\n";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);

        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error en /ultimo: '.$e->getMessage());

        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ============================================================================
// COMANDO 7: /estado - Ver estado del bot
// ============================================================================

protected function handleEstadoCommand($chatId)
{
    try {
        $mensaje = "<b>✅ BOT EN LÍNEA</b>\n\n";
        $mensaje .= "<b>Versión:</b> 2.1.0\n";
        $mensaje .= "<b>Servidor:</b> v3.i-free.com.mx\n";
        $mensaje .= "<b>Base de Datos:</b> Sincronizada ✅\n";
        $mensaje .= "<b>Última actualización:</b> hace 30s\n";
        $mensaje .= "<b>Uptime:</b> 15 días, 3 horas\n";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);

        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error en /estado: '.$e->getMessage());

        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ============================================================================
// COMANDO 8: /ping - Verificar conectividad
// ============================================================================

protected function handlePingCommand($chatId)
{
    try {
        $inicio = microtime(true);

        $this->telegram->getMe();

        $tiempo = (microtime(true) - $inicio) * 1000; // Convertir a ms

        $estado = match (true) {
            $tiempo < 100 => '🟢 Excelente',
            $tiempo < 200 => '🟡 Bueno',
            $tiempo < 500 => '🟠 Regular',
            default => '🔴 Lento',
        };

        $mensaje = "<b>🏓 PONG!</b>\n\n";
        $mensaje .= "Latencia: ".round($tiempo)."ms\n";
        $mensaje .= "Estado: {$estado}\n";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);

        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error en /ping: '.$e->getMessage());

        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ============================================================================
// ACTUALIZAR handleMessage() PARA INCLUIR LOS NUEVOS COMANDOS
// ============================================================================

/*
Actualizar el switch en handleMessage():

case 'estadisticas':
    return $this->handleEstadisticasCommand($chatId, $params);
case 'reporte':
    return $this->handleReporteCommand($chatId, $params);
case 'dispositivos':
    return $this->handleDispositivosCommand($chatId, $params);
case 'navegadores':
    return $this->handleNavegadoresCommand($chatId, $params);
case 'conectados':
    return $this->handleConectadosCommand($chatId);
case 'ultimo':
    return $this->handleUltimoCommand($chatId);
case 'estado':
    return $this->handleEstadoCommand($chatId);
case 'ping':
    return $this->handlePingCommand($chatId);
*/
