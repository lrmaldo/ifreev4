<?php

namespace App\Http\Controllers;

use App\Models\Zona;

/**
 * Vistas previas del portal cautivo de una zona (públicas: se comparten con prospectos).
 *
 * Usan la misma vista y lógica que el portal real (ZonaLoginController), en modo preview:
 * con el tema de la zona, sin registrar métricas ni respuestas, y al "conectar" llevan a
 * una página de confirmación en lugar de a un MikroTik.
 */
class ZonaController extends Controller
{
    /** Portal tal como lo verá un usuario (contenido según la configuración de la zona) */
    public function preview($id)
    {
        return $this->portal($id);
    }

    /** Portal mostrando una campaña de imágenes (si la zona tiene) */
    public function previewCarrusel($id)
    {
        return $this->portal($id, 'imagen');
    }

    /** Portal mostrando una campaña de video (si la zona tiene) */
    public function previewVideo($id)
    {
        return $this->portal($id, 'video');
    }

    /** Portal con las campañas de la zona */
    public function previewCampana($id)
    {
        return $this->portal($id);
    }

    /** Pantalla final de la preview: en el portal real, aquí el MikroTik da acceso a internet */
    public function previewConectado($id)
    {
        return view('portal.preview-conectado', ['zona' => Zona::findOrFail($id)]);
    }

    protected function portal($id, ?string $forzarTipo = null)
    {
        $zona = Zona::with(['campos' => fn ($query) => $query->orderBy('orden')])->findOrFail($id);

        // Datos que normalmente manda el MikroTik; la "salida" lleva a la confirmación de la preview
        $salida = route('cliente.zona.preview.conectado', ['id' => $zona->id]);
        $mikrotikData = [
            'mac' => '00:11:22:33:44:55',
            'mac-esc' => '00%3A11%3A22%3A33%3A44%3A55',
            'link-login-only' => $salida,
            'link-orig' => 'https://www.google.com/',
            'link-orig-esc' => 'https%3A%2F%2Fwww.google.com%2F',
            'chap-id' => '',
            'chap-challenge' => '',
            'error' => '',
        ];

        return app(ZonaLoginController::class)->mostrarPortalCautivo(
            $zona,
            $mikrotikData,
            ['zona_id' => $zona->id, 'tipo_visual' => 'portal_cautivo'],
            ['preview' => true, 'forzarTipo' => $forzarTipo]
        );
    }
}
