<?php

namespace App\Services;

use App\Models\AccessPoint;
use App\Models\AccessPointLectura;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\Zona;
use RuntimeException;

/**
 * Lee los access points por SNMP v2c (extensión php-snmp) y guarda su estado.
 *
 * Probado con TP-Link Omada EAP660 HD: la MIB privada de TP-Link (11863) da el número
 * de clientes y sus MAC; el tráfico sale del IF-MIB (eth0 es el uplink). No dan señal,
 * canal, CPU ni memoria.
 */
class AccessPointsService
{
    private const OID_SYS_NAME = '1.3.6.1.2.1.1.5.0';
    private const OID_UPTIME = '1.3.6.1.2.1.1.3.0';
    private const OID_IF_DESCR = '1.3.6.1.2.1.2.2.1.2';
    private const OID_IF_IN = '1.3.6.1.2.1.2.2.1.10';
    private const OID_IF_OUT = '1.3.6.1.2.1.2.2.1.16';
    private const OID_TPLINK_CLIENTES = '1.3.6.1.4.1.11863.10.1.1.1.0';
    private const OID_TPLINK_MACS = '1.3.6.1.4.1.11863.10.1.1.2.1.2';

    /** Interfaz cableada del AP: su tráfico de entrada es la bajada de los clientes */
    private const UPLINK = 'eth0';

    /** Días de historial por minuto que se conservan */
    public const DIAS_HISTORIAL = 30;

    /**
     * Consulta el AP, actualiza su última lectura y guarda una lectura en el historial.
     * Si no responde, lo marca fuera de línea con el error.
     */
    public function sondear(AccessPoint $ap): AccessPoint
    {
        $ahora = now();

        try {
            $datos = $this->leer($ap);
        } catch (\Throwable $e) {
            $ap->update([
                'en_linea' => false,
                'ultimo_error' => mb_substr($e->getMessage(), 0, 250),
                'ultimo_sondeo_at' => $ahora,
            ]);
            AccessPointLectura::create(['access_point_id' => $ap->id, 'created_at' => $ahora]);

            return $ap;
        }

        [$bajada, $subida] = $this->velocidades($ap->contadores, $datos['contadores'], $datos['uptime_seg'], $ahora->getTimestamp());

        $ap->update([
            'en_linea' => true,
            'modelo' => $datos['modelo'],
            'clientes' => $datos['clientes'],
            'macs' => $datos['macs'],
            'uptime_seg' => $datos['uptime_seg'],
            'bajada_bps' => $bajada,
            'subida_bps' => $subida,
            'contadores' => $datos['contadores'] + ['uptime' => $datos['uptime_seg'], 't' => $ahora->getTimestamp()],
            'ultimo_error' => null,
            'ultima_respuesta_at' => $ahora,
            'ultimo_sondeo_at' => $ahora,
        ]);

        AccessPointLectura::create([
            'access_point_id' => $ap->id,
            'clientes' => $datos['clientes'],
            'bajada_bps' => $bajada,
            'subida_bps' => $subida,
            'created_at' => $ahora,
        ]);

        return $ap;
    }

    /**
     * @return array{modelo: ?string, uptime_seg: int, clientes: int, macs: array<int, string>, contadores: array{entrada: ?int, salida: ?int}}
     */
    public function leer(AccessPoint $ap): array
    {
        if (!class_exists(\SNMP::class)) {
            throw new RuntimeException('Falta la extensión php-snmp en el servidor.');
        }

        $snmp = new \SNMP(\SNMP::VERSION_2c, "{$ap->host}:{$ap->puerto}", $ap->community, 1_500_000, 1);
        $snmp->valueretrieval = SNMP_VALUE_PLAIN;
        $snmp->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;
        $snmp->quick_print = true;
        $snmp->exceptions_enabled = \SNMP::ERRNO_ANY;

        try {
            $sistema = $snmp->get([self::OID_SYS_NAME, self::OID_UPTIME, self::OID_TPLINK_CLIENTES]);
        } catch (\SNMPException $e) {
            throw new RuntimeException(str_contains($e->getMessage(), 'Timeout') || str_contains($e->getMessage(), 'No response')
                ? 'No responde (revisa el AP, el NAT del MikroTik o la community).'
                : 'SNMP: ' . $e->getMessage());
        }

        $valor = fn (string $oid) => $sistema[$oid] ?? $sistema['.' . $oid] ?? null;

        return [
            'modelo' => trim((string) $valor(self::OID_SYS_NAME), "\" \0") ?: null,
            'uptime_seg' => intdiv((int) $valor(self::OID_UPTIME), 100),
            'clientes' => (int) $valor(self::OID_TPLINK_CLIENTES),
            'macs' => $this->macs($snmp),
            'contadores' => $this->contadoresUplink($snmp),
        ];
    }

    /**
     * Bajada y subida en bits por segundo desde la lectura anterior. Null si no hay
     * lectura anterior, pasó mucho tiempo o el AP se reinició (los contadores vuelven a 0).
     *
     * @return array{0: ?int, 1: ?int}
     */
    public function velocidades(?array $antes, array $ahora, int $uptime, int $t): array
    {
        $segundos = $t - (int) ($antes['t'] ?? 0);

        if (!$antes || $segundos <= 0 || $segundos > 600 || $uptime < (int) ($antes['uptime'] ?? 0)) {
            return [null, null];
        }

        // Los contadores son de 32 bits: si el actual es menor, dio la vuelta
        $delta = function (?int $previo, ?int $actual) {
            if ($previo === null || $actual === null) {
                return null;
            }

            return $actual >= $previo ? $actual - $previo : $actual + 4294967296 - $previo;
        };

        $bps = fn (?int $bytes) => $bytes === null ? null : (int) round($bytes * 8 / $segundos);

        return [
            $bps($delta($antes['entrada'] ?? null, $ahora['entrada'])),
            $bps($delta($antes['salida'] ?? null, $ahora['salida'])),
        ];
    }

    /**
     * Cuántas de las MAC conectadas a cada AP ya pasaron por el portal hoy y cuántas se registraron.
     *
     * @return array{portal: int, registrados: int}
     */
    public function cruceConPortal(AccessPoint $ap): array
    {
        $macs = $ap->macs ?? [];

        if (!$ap->zona_id || !$macs) {
            return ['portal' => 0, 'registrados' => 0];
        }

        return [
            'portal' => HotspotMetric::where('zona_id', $ap->zona_id)
                ->whereIn('mac_address', $macs)
                ->where('updated_at', '>=', now()->startOfDay())
                ->distinct()
                ->count('mac_address'),
            'registrados' => FormResponse::where('zona_id', $ap->zona_id)
                ->whereIn('mac_address', $macs)
                ->distinct()
                ->count('mac_address'),
        ];
    }

    /**
     * Resumen para la pantalla en vivo: solo nombre, clientes y si responde.
     *
     * @return array<int, array{nombre: string, clientes: int, en_linea: bool}>
     */
    public function resumenZona(Zona $zona): array
    {
        return AccessPoint::where('zona_id', $zona->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (AccessPoint $ap) => [
                'nombre' => $ap->nombre,
                'clientes' => $this->respondeReciente($ap) ? (int) $ap->clientes : 0,
                'en_linea' => $this->respondeReciente($ap),
            ])
            ->all();
    }

    /** Respondió en los últimos 3 minutos */
    public function respondeReciente(AccessPoint $ap): bool
    {
        return $ap->en_linea && $ap->ultima_respuesta_at?->gt(now()->subMinutes(3));
    }

    public function borrarHistorialViejo(): int
    {
        return AccessPointLectura::where('created_at', '<', now()->subDays(self::DIAS_HISTORIAL))->delete();
    }

    /**
     * MAC de los clientes conectados ("7C-8B-CA-64-4F-93" → "7C:8B:CA:64:4F:93", como las guarda el portal).
     *
     * @return array<int, string>
     */
    private function macs(\SNMP $snmp): array
    {
        try {
            $tabla = $snmp->walk(self::OID_TPLINK_MACS);
        } catch (\SNMPException) {
            // Sin clientes la tabla viene vacía y algunos agentes lo reportan como error
            return [];
        }

        return collect($tabla ?: [])
            ->map(fn ($valor) => strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', (string) $valor)))
            ->filter(fn ($hex) => strlen($hex) === 12)
            ->map(fn ($hex) => implode(':', str_split($hex, 2)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Bytes acumulados del uplink: entrada (bajada de los clientes) y salida (subida).
     *
     * @return array{entrada: ?int, salida: ?int}
     */
    private function contadoresUplink(\SNMP $snmp): array
    {
        try {
            $nombres = $snmp->walk(self::OID_IF_DESCR);
        } catch (\SNMPException) {
            return ['entrada' => null, 'salida' => null];
        }

        $indice = null;
        foreach ($nombres ?: [] as $oid => $nombre) {
            if (trim((string) $nombre, "\" \0") === self::UPLINK) {
                $indice = substr((string) $oid, strrpos((string) $oid, '.') + 1);
                break;
            }
        }

        if ($indice === null) {
            return ['entrada' => null, 'salida' => null];
        }

        try {
            $valores = array_values($snmp->get([self::OID_IF_IN . ".{$indice}", self::OID_IF_OUT . ".{$indice}"]));
        } catch (\SNMPException) {
            return ['entrada' => null, 'salida' => null];
        }

        return ['entrada' => (int) $valores[0], 'salida' => (int) $valores[1]];
    }
}
