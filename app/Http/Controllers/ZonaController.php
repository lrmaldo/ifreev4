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
        $campanasActivas = \App\Models\Campana::where('visible', true)
            ->where('fecha_inicio', '<=', now()->format('Y-m-d'))
            ->where('fecha_fin', '>=', now()->format('Y-m-d'))
            ->where(function($query) {
                $diaSemana = strtolower(now()->locale('es')->dayName);
                $query->where('siempre_visible', true)
                      ->orWhere(function($q) use ($diaSemana) {
                          $q->where('siempre_visible', false)
                            ->whereJsonContains('dias_visibles', $diaSemana);
                      });
            })
            ->where(function($query) use ($zona) {
                $query->where('cliente_id', $zona->cliente_id)
                      ->orWhereNull('cliente_id'); // Incluir también campañas globales
            })
            ->get();

        // Filtrar solo campañas de tipo 'imagenes'
        $campanasImagenes = $campanasActivas->where('tipo', 'imagenes');

        // Seleccionar campaña según criterio configurado
        $campanaSeleccionada = null;
        $imagenes = [];

        if (!$campanasImagenes->isEmpty()) {
            // Modo de selección (prioridad o aleatorio)
            $modoSeleccion = $zona->seleccion_campanas ?? 'prioridad';

            if ($modoSeleccion === 'aleatorio') {
                $campanaSeleccionada = $campanasImagenes->random();
            } else { // prioridad
                $campanaSeleccionada = $campanasImagenes->sortBy('prioridad')->first();
            }

            // Obtener imágenes de la campaña seleccionada
            if ($campanaSeleccionada) {
                // En un caso real, aquí cargaríamos las imágenes asociadas a la campaña
                // Para esta demostración, usamos imágenes de placeholder
                $imagenes = [
                    asset('storage/campanas/imagen1.jpg'),
                    asset('storage/campanas/imagen2.jpg'),
                    asset('storage/campanas/imagen3.jpg'),
                ];
            }
        } else {
            // Si no hay campañas de tipo imagen, usar imágenes por defecto
            $imagenes = [
                'https://via.placeholder.com/800x400/ff5e2c/ffffff?text=Bienvenido+a+nuestra+red+WiFi',
                'https://via.placeholder.com/800x400/ff8159/ffffff?text=Disfruta+de+nuestra+conectividad',
                'https://via.placeholder.com/800x400/e64a1c/ffffff?text=Gracias+por+tu+visita',
            ];
        }

        // Tiempo de visualización configurable (segundos)
        $tiempoVisualizacion = $zona->tiempo_visualizacion ?? 15;

        return view('zonas.preview-carrusel', compact('zona', 'mikrotikData', 'camposHtml', 'imagenes', 'campanaSeleccionada', 'tiempoVisualizacion'));
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

        // Obtener campañas activas para el cliente de la zona o las globales
        $campanasActivas = \App\Models\Campana::where('visible', true)
            ->where('fecha_inicio', '<=', now()->format('Y-m-d'))
            ->where('fecha_fin', '>=', now()->format('Y-m-d'))
            ->where(function($query) {
                $diaSemana = strtolower(now()->locale('es')->dayName);
                $query->where('siempre_visible', true)
                      ->orWhere(function($q) use ($diaSemana) {
                          $q->where('siempre_visible', false)
                            ->whereJsonContains('dias_visibles', $diaSemana);
                      });
            })
            ->where(function($query) use ($zona) {
                $query->where('cliente_id', $zona->cliente_id)
                      ->orWhereNull('cliente_id'); // Incluir también campañas globales
            })
            ->get();

        // Filtrar solo campañas de tipo 'video'
        $campanasVideo = $campanasActivas->where('tipo', 'video');

        // Seleccionar campaña según criterio configurado
        $campanaSeleccionada = null;
        $videoUrl = '';

        if (!$campanasVideo->isEmpty()) {
            // Modo de selección (prioridad o aleatorio)
            $modoSeleccion = $zona->seleccion_campanas ?? 'prioridad';

            if ($modoSeleccion === 'aleatorio') {
                $campanaSeleccionada = $campanasVideo->random();
            } else { // prioridad
                $campanaSeleccionada = $campanasVideo->sortBy('prioridad')->first();
            }

            // Obtener video de la campaña seleccionada
            if ($campanaSeleccionada) {
                // En un caso real, aquí cargaríamos el video asociado a la campaña
                // Para esta demostración, usamos un video por defecto
                $videoUrl = asset('storage/campanas/video.mp4');
            }
        } else {
            // Si no hay campañas de tipo video, usar video por defecto
            $videoUrl = asset('videos/sample.mp4');
        }

        return view('zonas.preview-video', compact('zona', 'mikrotikData', 'camposHtml', 'videoUrl', 'campanaSeleccionada'));
    }

    /**
     * Muestra una vista previa dinámica del portal cautivo con sistema de campañas.
     * Selecciona automáticamente entre carrusel de imágenes y video según campañas activas.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function previewCampana($id)
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
        $campanasActivas = \App\Models\Campana::where('visible', true)
            ->where('fecha_inicio', '<=', now()->format('Y-m-d'))
            ->where('fecha_fin', '>=', now()->format('Y-m-d'))
            ->where(function($query) {
                $diaSemana = strtolower(now()->locale('es')->dayName);
                $query->where('siempre_visible', true)
                      ->orWhere(function($q) use ($diaSemana) {
                          $q->where('siempre_visible', false)
                            ->whereJsonContains('dias_visibles', $diaSemana);
                      });
            })
            ->where(function($query) use ($zona) {
                $query->where('cliente_id', $zona->cliente_id)
                      ->orWhereNull('cliente_id'); // Incluir también campañas globales
            })
            ->get();

        // Filtrar campañas por tipo
        $campanasVideo = $campanasActivas->where('tipo', 'video');
        $campanasImagenes = $campanasActivas->where('tipo', 'imagenes');

        // Variables para la vista
        $tipoCampana = 'imagenes'; // tipo por defecto
        $campanaSeleccionada = null;
        $contenido = [];
        $tiempoVisualizacion = $zona->tiempo_visualizacion ?? 15;

        // Modo de selección (prioridad o aleatorio)
        $modoSeleccion = $zona->seleccion_campanas ?? 'prioridad';

        // LÓGICA DE PRIORIDAD: Los videos tienen prioridad sobre las imágenes
        if (!$campanasVideo->isEmpty()) {
            // Hay campañas de video disponibles - priorizarlas
            $tipoCampana = 'video';

            if ($modoSeleccion === 'aleatorio') {
                $campanaSeleccionada = $campanasVideo->random();
            } else { // prioridad
                $campanaSeleccionada = $campanasVideo->sortBy('prioridad')->first();
            }

            // Preparar contenido de video
            if ($campanaSeleccionada) {
                // En un caso real, aquí cargaríamos el video asociado a la campaña
                // Para esta demostración, usamos un video por defecto
                $contenido = [
                    'url' => asset('storage/campanas/video.mp4'),
                    'poster' => asset('storage/campanas/video-poster.jpg'),
                    'titulo' => $campanaSeleccionada->nombre ?? 'Video promocional'
                ];
            } else {
                // Video por defecto si no hay campañas de video configuradas
                $contenido = [
                    'url' => asset('videos/sample.mp4'),
                    'poster' => 'https://via.placeholder.com/800x450/ff5e2c/ffffff?text=Video+Promocional',
                    'titulo' => 'Bienvenido a nuestra red WiFi'
                ];
            }
        } elseif (!$campanasImagenes->isEmpty()) {
            // No hay videos, usar campañas de imágenes
            $tipoCampana = 'imagenes';

            if ($modoSeleccion === 'aleatorio') {
                $campanaSeleccionada = $campanasImagenes->random();
            } else { // prioridad
                $campanaSeleccionada = $campanasImagenes->sortBy('prioridad')->first();
            }

            // Preparar contenido de imágenes
            if ($campanaSeleccionada) {
                // En un caso real, aquí cargaríamos las imágenes asociadas a la campaña
                // Para esta demostración, usamos imágenes de placeholder
                $contenido = [
                    asset('storage/campanas/imagen1.jpg'),
                    asset('storage/campanas/imagen2.jpg'),
                    asset('storage/campanas/imagen3.jpg'),
                ];
            } else {
                // Imágenes por defecto si no hay campañas configuradas
                $contenido = [
                    'https://via.placeholder.com/800x400/ff5e2c/ffffff?text=Bienvenido+a+nuestra+red+WiFi',
                    'https://via.placeholder.com/800x400/ff8159/ffffff?text=Disfruta+de+nuestra+conectividad',
                    'https://via.placeholder.com/800x400/e64a1c/ffffff?text=Gracias+por+tu+visita',
                ];
            }
        } else {
            // No hay campañas activas - usar contenido por defecto de imágenes
            $tipoCampana = 'imagenes';
            $contenido = [
                'https://via.placeholder.com/800x400/ff5e2c/ffffff?text=Bienvenido+a+nuestra+red+WiFi',
                'https://via.placeholder.com/800x400/ff8159/ffffff?text=Disfruta+de+nuestra+conectividad',
                'https://via.placeholder.com/800x400/e64a1c/ffffff?text=Gracias+por+tu+visita',
            ];
        }

        // Datos adicionales para debugging (opcional)
        $debugInfo = [
            'campanasActivas' => $campanasActivas->count(),
            'campanasVideo' => $campanasVideo->count(),
            'campanasImagenes' => $campanasImagenes->count(),
            'modoSeleccion' => $modoSeleccion,
            'tipoCampanaSeleccionada' => $tipoCampana,
            'campanaNombre' => $campanaSeleccionada->nombre ?? 'Sin campaña específica'
        ];

        return view('zonas.preview-campana', compact(
            'zona',
            'mikrotikData',
            'camposHtml',
            'tipoCampana',
            'contenido',
            'campanaSeleccionada',
            'tiempoVisualizacion',
            'debugInfo'
        ));
    }
}
