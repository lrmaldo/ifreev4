<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ZonaLoginController extends Controller
{
    /**
     * Maneja las solicitudes POST enviadas desde el portal cautivo Mikrotik.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  ID de la zona (puede ser el ID real o personalizado)
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, $id)
    {
        // Buscar la zona primero por id_personalizado, luego por id
        $zona = \App\Models\Zona::where('id_personalizado', $id)->first();

        if (!$zona) {
            $zona = \App\Models\Zona::find($id);
        }

        if (!$zona) {
            abort(404, 'Zona no encontrada');
        }

        // Capturar todos los parámetros enviados por Mikrotik
        $mikrotikData = $request->only([
            'mac', 'ip', 'username', 'link-login', 'link-orig', 'error',
            'chap-id', 'chap-challenge', 'link-login-only', 'link-orig-esc', 'mac-esc'
        ]);

        // Aquí puedes procesar los datos como sea necesario
        // Por ejemplo, guardarlos en una base de datos, verificar si el usuario está autorizado, etc.

        // Dependiendo del tipo de registro configurado para esta zona,
        // redirigimos al formulario correspondiente o aplicamos otra lógica
        switch ($zona->tipo_registro) {
            case 'formulario':
                // Redirigir a un formulario de registro con los datos de Mikrotik
                return redirect()
                    ->route('zona.registro.formulario', ['zonaId' => $zona->id])
                    ->with('mikrotik_data', $mikrotikData);

            case 'redes':
                // Redirigir a registro con redes sociales
                return redirect()
                    ->route('zona.registro.redes', ['zonaId' => $zona->id])
                    ->with('mikrotik_data', $mikrotikData);

            case 'sin_registro':
                // No se requiere registro, autenticar directamente
                return $this->autenticarSinRegistro($zona, $mikrotikData);

            default:
                // Caso por defecto, redirigir al formulario estándar
                return redirect()
                    ->route('zona.registro.formulario', ['zonaId' => $zona->id])
                    ->with('mikrotik_data', $mikrotikData);
        }
    }

    /**
     * Autenticar al usuario sin requerir registro.
     *
     * @param  \App\Models\Zona  $zona
     * @param  array  $mikrotikData
     * @return \Illuminate\Http\Response
     */
    protected function autenticarSinRegistro($zona, $mikrotikData)
    {
        // Aquí iría la lógica para autenticar al usuario directamente sin registro
        // Por ejemplo, podrías generar una respuesta que redirija al usuario a la URL correcta
        // con los parámetros necesarios para la autenticación en Mikrotik

        // Por ahora, simulamos una respuesta básica
        return view('auth.mikrotik.direct-auth', [
            'zona' => $zona,
            'mikrotikData' => $mikrotikData
        ]);
    }
}
