<?php

namespace App\Services;

use App\Models\FormField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Revisa y limpia lo que la gente escribe en el formulario del portal cautivo:
 * teléfono a 10 dígitos, correo con dominio real y nombre con apellido.
 * Lo mismo se marca en el HTML (pattern, inputmode) para que el celular avise antes de enviar.
 */
class ValidacionPortal
{
    /** Dominios reales: se aceptan tal cual y sirven para detectar errores de dedo. */
    public const DOMINIOS_CONOCIDOS = [
        'gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'yahoo.com.mx', 'icloud.com',
        'live.com', 'live.com.mx', 'hotmail.es', 'outlook.es', 'prodigy.net.mx', 'me.com',
        'msn.com', 'aol.com', 'ymail.com', 'mail.com', 'gmx.com', 'protonmail.com', 'proton.me',
    ];

    /** Los que se sugieren cuando el dominio se parece a uno de ellos ("gmial.com"). */
    private const DOMINIOS_SUGERIDOS = [
        'gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'yahoo.com.mx', 'icloud.com', 'live.com.mx',
    ];

    public const PATRON_TELEFONO = '[2-9][0-9]{9}';
    public const PATRON_NOMBRE = '\s*\p{L}[\p{L}\'.\-]+(\s+\p{L}[\p{L}\'.\-]+)+\s*';

    public const MENSAJE_TELEFONO = 'Escribe tu teléfono a 10 dígitos, sin lada internacional.';
    public const MENSAJE_CORREO = 'Escribe un correo válido, por ejemplo nombre@gmail.com.';
    public const MENSAJE_NOMBRE = 'Escribe tu nombre y apellido.';

    public static function esNombre(FormField $campo): bool
    {
        if ($campo->tipo !== 'text') {
            return false;
        }

        $texto = Str::lower(Str::ascii($campo->campo . ' ' . $campo->etiqueta));

        return Str::contains($texto, 'nombre')
            && ! Str::contains($texto, ['empresa', 'negocio', 'comercial', 'compania', 'usuario', 'red', 'isp', 'marca']);
    }

    /**
     * Limpia y valida las respuestas. Devuelve las respuestas listas para guardar
     * o lanza ValidationException con el mensaje de cada campo.
     */
    public function validar(Collection $campos, array $respuestas, bool $exigirObligatorios = true): array
    {
        $reglas = [];
        $atributos = [];

        foreach ($campos as $campo) {
            $clave = $campo->campo;
            $valor = $respuestas[$clave] ?? null;

            if (is_string($valor)) {
                $respuestas[$clave] = $valor = $this->limpiar($campo, $valor);
            }

            $regla = [($exigirObligatorios && $campo->obligatorio && $campo->tipo !== 'checkbox') ? 'required' : 'nullable'];

            if ($campo->tipo === 'tel') {
                $regla[] = fn ($attr, $v, $falla) => $this->telefonoValido((string) $v) ?: $falla(self::MENSAJE_TELEFONO);
            } elseif ($campo->tipo === 'email') {
                $regla[] = fn ($attr, $v, $falla) => ($error = $this->errorCorreo((string) $v)) ? $falla($error) : null;
            } elseif (in_array($campo->tipo, ['select', 'radio'], true) && $campo->opciones->isNotEmpty()) {
                $regla[] = Rule::in($campo->opciones->pluck('valor')->map(fn ($v) => (string) $v)->all());
            } elseif (self::esNombre($campo)) {
                $regla[] = fn ($attr, $v, $falla) => $this->nombreValido((string) $v) ?: $falla(self::MENSAJE_NOMBRE);
            }

            $reglas["respuestas.{$clave}"] = $regla;
            $atributos["respuestas.{$clave}"] = Str::lower($campo->etiqueta);
        }

        Validator::make(['respuestas' => $respuestas], $reglas, [
            'required' => 'Falta tu :attribute.',
            'in' => 'Elige una opción de la lista en :attribute.',
        ], $atributos)->validate();

        return $respuestas;
    }

    private function limpiar(FormField $campo, string $valor): string
    {
        $valor = trim(preg_replace('/\s+/u', ' ', $valor));

        if ($campo->tipo === 'tel') {
            $digitos = preg_replace('/\D/', '', $valor);
            // +52 55 1234 5678 o el viejo 521 de celulares
            if (strlen($digitos) === 12 && str_starts_with($digitos, '52')) {
                $digitos = substr($digitos, 2);
            } elseif (strlen($digitos) === 13 && str_starts_with($digitos, '521')) {
                $digitos = substr($digitos, 3);
            }

            return $digitos;
        }

        if ($campo->tipo === 'email') {
            return Str::lower(str_replace(' ', '', $valor));
        }

        // "PEDRO PÉREZ" o "pedro pérez" se guarda como "Pedro Pérez"
        if (self::esNombre($campo) && (mb_strtoupper($valor) === $valor || mb_strtolower($valor) === $valor)) {
            return mb_convert_case($valor, MB_CASE_TITLE, 'UTF-8');
        }

        return $valor;
    }

    private function telefonoValido(string $telefono): bool
    {
        return preg_match('/^' . self::PATRON_TELEFONO . '$/', $telefono)
            && ! preg_match('/^(\d)\1+$/', $telefono)
            && ! in_array($telefono, ['2345678901', '9876543210'], true);
    }

    private function errorCorreo(string $correo): ?string
    {
        if (! filter_var($correo, FILTER_VALIDATE_EMAIL) || ! str_contains(Str::after($correo, '@'), '.')) {
            return self::MENSAJE_CORREO;
        }

        [$usuario, $dominio] = explode('@', $correo, 2);

        if (! in_array($dominio, self::DOMINIOS_CONOCIDOS, true)) {
            foreach (self::DOMINIOS_SUGERIDOS as $sugerido) {
                if (levenshtein($dominio, $sugerido) <= 2) {
                    return "¿Quisiste decir {$usuario}@{$sugerido}?";
                }
            }

            if (config('ifree.validar_correo_dns') && ! $this->dominioRecibeCorreo($dominio)) {
                return "El dominio {$dominio} no recibe correos. Revisa que esté bien escrito.";
            }
        }

        return null;
    }

    /** MX o, si no tiene, registro A (así lo permite el estándar). */
    protected function dominioRecibeCorreo(string $dominio): bool
    {
        return checkdnsrr($dominio . '.', 'MX') || checkdnsrr($dominio . '.', 'A');
    }

    private function nombreValido(string $nombre): bool
    {
        if (! preg_match('/^' . self::PATRON_NOMBRE . '$/u', $nombre)) {
            return false;
        }

        // "aaa bbb" o "xx xx" no son nombres
        foreach (explode(' ', $nombre) as $palabra) {
            if (preg_match('/^(.)\1+$/u', mb_strtolower($palabra))) {
                return false;
            }
        }

        return true;
    }
}
