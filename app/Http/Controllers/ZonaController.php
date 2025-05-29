<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Zona;
use App\Traits\RenderizaFormFields;

class ZonaController extends Controller
{
    use RenderizaFormFields;

    /**
     * Muestra una vista previa de cómo se verá el portal cautivo para una zona específica.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function preview($id)
    {
        // Obtener la zona con sus campos de formulario
        $zona = Zona::with(['campos' => function ($query) {
            $query->orderBy('orden');
        }])->findOrFail($id);

        // Datos simulados que normalmente enviaría Mikrotik
        $mikrotikData = [
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'username' => '',
            'link-login' => 'http://10.0.0.1/login',
            'link-orig' => 'http://www.google.com/',
            'error' => '',
            'chap-id' => '12345678',
            'chap-challenge' => 'abcdef1234567890',
            'link-login-only' => 'http://10.0.0.1/login',
            'link-orig-esc' => 'http%3A%2F%2Fwww.google.com%2F',
            'mac-esc' => '00%3A11%3A22%3A33%3A44%3A55'
        ];

        // Pre-renderizar los campos del formulario
        $camposHtml = [];
        foreach ($zona->campos as $campo) {
            $camposHtml[] = $this->renderizarCampo($campo);
        }

        return view('zonas.preview', compact('zona', 'mikrotikData', 'camposHtml'));
    }

    /**
     * Muestra una vista previa del portal cautivo con carrusel de imágenes.
     * Muestra el formulario y al enviar, muestra un carrusel de imágenes con contador regresivo.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function previewCarrusel($id)
    {
        // Obtener la zona con sus campos de formulario
        $zona = Zona::with(['campos' => function ($query) {
            $query->orderBy('orden');
        }])->findOrFail($id);

        // Datos simulados que normalmente enviaría Mikrotik
        $mikrotikData = [
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'username' => '',
            'link-login' => 'http://10.0.0.1/login',
            'link-orig' => 'http://www.google.com/',
            'error' => '',
            'chap-id' => '12345678',
            'chap-challenge' => 'abcdef1234567890',
            'link-login-only' => 'http://10.0.0.1/login',
            'link-orig-esc' => 'http%3A%2F%2Fwww.google.com%2F',
            'mac-esc' => '00%3A11%3A22%3A33%3A44%3A55'
        ];

        // Pre-renderizar los campos del formulario
        $camposHtml = [];
        foreach ($zona->campos as $campo) {
            $camposHtml[] = $this->renderizarCampo($campo);
        }

        // Obtener campañas activas para el cliente de la zona o las globales
        $campanas = \App\Models\Campana::activas()
            ->where(function($query) use ($zona) {
                $query->where('cliente_id', $zona->cliente_id)
                      ->orWhereNull('cliente_id'); // Incluir también campañas globales
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('zonas.preview-carrusel', compact('zona', 'mikrotikData', 'camposHtml', 'campanas'));
    }

    /**
     * Muestra una vista previa del portal cautivo con reproducción de video.
     * Muestra el formulario y al enviar, reproduce un video.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function previewVideo($id)
    {
        // Obtener la zona con sus campos de formulario
        $zona = Zona::with(['campos' => function ($query) {
            $query->orderBy('orden');
        }])->findOrFail($id);

        // Datos simulados que normalmente enviaría Mikrotik
        $mikrotikData = [
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'username' => '',
            'link-login' => 'http://10.0.0.1/login',
            'link-orig' => 'http://www.google.com/',
            'error' => '',
            'chap-id' => '12345678',
            'chap-challenge' => 'abcdef1234567890',
            'link-login-only' => 'http://10.0.0.1/login',
            'link-orig-esc' => 'http%3A%2F%2Fwww.google.com%2F',
            'mac-esc' => '00%3A11%3A22%3A33%3A44%3A55'
        ];

        // Pre-renderizar los campos del formulario
        $camposHtml = [];
        foreach ($zona->campos as $campo) {
            $camposHtml[] = $this->renderizarCampo($campo);
        }

        // URL del video de ejemplo
        $videoUrl = asset('videos/sample.mp4');

        return view('zonas.preview-video', compact('zona', 'mikrotikData', 'camposHtml', 'videoUrl'));
    }
}
