<?php

namespace App\Services;

/**
 * Token firmado que el portal cautivo entrega en cada visita.
 *
 * Amarra la zona y la MAC que recibió el portal desde el MikroTik, para que los
 * endpoints públicos (métricas y formularios) no acepten zona_id/mac_address
 * inventados por quien los llame directamente.
 */
class PortalToken
{
    /** Vigencia del token en minutos */
    public const VIGENCIA_MINUTOS = 720;

    public static function generar(int $zonaId, string $mac, ?int $vigenciaMinutos = null): string
    {
        $payload = self::base64url(json_encode([
            'z' => $zonaId,
            'm' => $mac,
            'e' => now()->addMinutes($vigenciaMinutos ?? self::VIGENCIA_MINUTOS)->timestamp,
        ]));

        return $payload . '.' . self::firma($payload);
    }

    /**
     * @return array{zona_id: int, mac_address: string}|null  null si el token es inválido o expiró
     */
    public static function validar(?string $token): ?array
    {
        if (!$token || substr_count($token, '.') !== 1) {
            return null;
        }

        [$payload, $firma] = explode('.', $token);

        if (!hash_equals(self::firma($payload), $firma)) {
            return null;
        }

        $datos = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        if (!is_array($datos) || !isset($datos['z'], $datos['m'], $datos['e']) || $datos['e'] < now()->timestamp) {
            return null;
        }

        return ['zona_id' => (int) $datos['z'], 'mac_address' => (string) $datos['m']];
    }

    private static function firma(string $payload): string
    {
        return self::base64url(hash_hmac('sha256', 'portal|' . $payload, config('app.key'), true));
    }

    private static function base64url(string $valor): string
    {
        return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    }
}
