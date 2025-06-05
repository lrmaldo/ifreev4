<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Zona;
use App\Traits\RenderizaFormFields;
use Illuminate\Support\Facades\Storage;

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

        // Verificar si la zona tiene tipo de registro "sin_registro" o si no tiene campos
        $mostrarFormulario = $zona->tipo_registro != 'sin_registro' && $zona->campos->count() > 0;

        return view('zonas.preview', compact('zona', 'mikrotikData', 'camposHtml', 'mostrarFormulario'));
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

        // Verificar si la zona tiene tipo de registro "sin_registro" o si no tiene campos
        $mostrarFormulario = $zona->tipo_registro != 'sin_registro' && $zona->campos->count() > 0;

        // Pre-renderizar los campos del formulario
        $camposHtml = [];
        foreach ($zona->campos as $campo) {
            $camposHtml[] = $this->renderizarCampo($campo);
        }

        // Obtener campañas activas de la zona usando el método del modelo
        $campanasActivas = $zona->getCampanasActivas();

        // Debug: Imprimir las campañas activas para revisar
        \Log::info('Campañas activas para la zona ' . $zona->id . ': ' . $campanasActivas->count());
        foreach ($campanasActivas as $campana) {
            \Log::info('Campaña ID: ' . $campana->id . ', Título: ' . $campana->titulo . ', Tipo: ' . $campana->tipo . ', Archivo: ' . $campana->archivo_path);
        }

        // Forzar recarga de relaciones de la base de datos para asegurarnos de tener datos actualizados
        try {
            // Refrescar manualmente las relaciones en la tabla pivot
            $relacionesPivot = \DB::table('campana_zona')->where('zona_id', $zona->id)->get();
            \Log::info("Relaciones en tabla pivot para zona {$zona->id}: " . $relacionesPivot->count());

            // Si no hay relaciones en la tabla pivot pero hay campañas activas, crearlas
            if ($relacionesPivot->isEmpty() && !$campanasActivas->isEmpty()) {
                \Log::info("Creando relaciones pivot que faltan para {$campanasActivas->count()} campañas activas");
                foreach ($campanasActivas as $campana) {
                    \DB::table('campana_zona')->insert([
                        'zona_id' => $zona->id,
                        'campana_id' => $campana->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error verificando relaciones pivot: " . $e->getMessage());
        }

        // Preparamos un array con los tipos de campañas
        $tiposCampanas = [];
        foreach ($campanasActivas as $campana) {
            $tiposCampanas[] = $campana->tipo;
        }

        // Filtrar campañas de tipo 'imagen' o 'imagenes' o cualquier variación
        $campanasImagenes = $campanasActivas->filter(function($campana) {
            // Si no tiene tipo definido, asumimos que es una imagen por defecto
            if (!isset($campana->tipo) || empty($campana->tipo)) {
                return true;
            }

            $tipo = strtolower($campana->tipo ?? '');
            return $tipo === 'imagen' || $tipo === 'imagenes' || $tipo === 'image' || $tipo === 'img' ||
                   strpos($tipo, 'imag') !== false;
        });

        \Log::info("Campañas de tipo imagen encontradas: " . $campanasImagenes->count());

        // Inicializar variables
        $campanaSeleccionada = null;
        $imagenes = [];

        // Si no hay campañas activas de tipo imagen, vamos a buscar TODAS las imágenes
        // disponibles en el directorio de campañas
        if ($campanasImagenes->isEmpty()) {
            \Log::info("No hay campañas activas. Buscando imágenes en el directorio...");
            // Buscar en el directorio físico todas las imágenes disponibles
            if (is_dir(public_path('storage/campanas/imagenes'))) {
                $files = scandir(public_path('storage/campanas/imagenes'));
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
                        $rutaImagen = '/storage/campanas/imagenes/' . $file;
                        $imagenes[] = $rutaImagen;
                        \Log::info("Imagen encontrada en directorio: " . $rutaImagen);
                    }
                }
            }

            // Si aún así no hay imágenes, usamos al menos la imagen default
            if (empty($imagenes) && file_exists(public_path('storage/campanas/imagenes/default.jpg'))) {
                $imagenes[] = '/storage/campanas/imagenes/default.jpg';
                \Log::info("Usando imagen default.jpg como respaldo");
            }
        }

        // Si hay campañas activas, procesamos normalmente
        if (!$campanasImagenes->isEmpty()) {
            // Mostrar todas las imágenes de campañas en el carrusel
            // (Limitamos a máximo 10 imágenes para evitar sobrecargar el carrusel)
            $campanasAMostrar = $campanasImagenes->take(10);

            // Seleccionar la primera campaña como la principal para mostrar información
            $modoSeleccion = $zona->seleccion_campanas ?? 'prioridad';

            if ($modoSeleccion === 'aleatorio') {
                // Para el modo aleatorio, seleccionamos una al azar como la principal
                $campanaSeleccionada = $campanasImagenes->random();
            } else {
                // Para modo prioridad, tomamos la de mayor prioridad como principal
                $campanaSeleccionada = $campanasImagenes->sortBy('prioridad')->first();
            }

            // Asegurar que la campaña seleccionada se asocie correctamente con esta zona
            if ($campanaSeleccionada) {
                try {
                    // Verificar si la relación ya existe
                    if (!\DB::table('campana_zona')->where('zona_id', $zona->id)->where('campana_id', $campanaSeleccionada->id)->exists()) {
                        \Log::info("Asociando campaña seleccionada en previewCarrusel: Zona {$zona->id} con Campaña {$campanaSeleccionada->id}");
                        $zona->campanas()->attach($campanaSeleccionada->id);
                    }
                } catch (\Exception $e) {
                    \Log::error("Error al asociar campaña seleccionada con zona en previewCarrusel: " . $e->getMessage());
                }
            }

            // Obtener todas las imágenes de las campañas seleccionadas
            foreach ($campanasAMostrar as $campana) {
                \Log::info("Procesando campaña ID {$campana->id} para carrusel, archivo_path: " . ($campana->archivo_path ?? 'null'));

                if ($campana->archivo_path) {
                    // Intentar varias rutas posibles hasta encontrar una que funcione
                    $posiblesRutas = [
                        // Ruta directa desde storage
                        public_path('storage/' . $campana->archivo_path),
                        // Ruta para archivos guardados con Storage::put
                        public_path($campana->archivo_path),
                        // Ruta para archivos en la carpeta específica de campañas/imagenes
                        public_path('storage/campanas/imagenes/' . basename($campana->archivo_path)),
                        // Ruta absoluta si el archivo_path ya es una ruta completa
                        public_path($campana->archivo_path),
                    ];

                    $rutaEncontrada = false;
                    $rutaImagen = '';

                    foreach ($posiblesRutas as $index => $ruta) {
                        \Log::info("Comprobando ruta #{$index}: {$ruta}");
                        if (file_exists($ruta)) {
                            // Convertir ruta de archivo a URL
                            if ($index == 0) {
                                $rutaImagen = '/storage/' . $campana->archivo_path;
                            } elseif ($index == 1) {
                                $rutaImagen = '/' . $campana->archivo_path;
                            } elseif ($index == 2) {
                                $rutaImagen = '/storage/campanas/imagenes/' . basename($campana->archivo_path);
                            } else {
                                $rutaImagen = '/' . ltrim($campana->archivo_path, '/');
                            }

                            \Log::info("Archivo encontrado, URL para carrusel: {$rutaImagen}");
                            $rutaEncontrada = true;
                            $imagenes[] = $rutaImagen;
                            break;
                        }
                    }

                    // Si no encontramos el archivo, intentar con Storage::url como último recurso
                    if (!$rutaEncontrada) {
                        try {
                            $rutaImagen = Storage::url($campana->archivo_path);
                            $imagenes[] = $rutaImagen;
                        } catch (\Exception $e) {
                            \Log::error("Error al obtener URL del archivo: " . $e->getMessage());
                        }
                    }

                    \Log::info('Información de campaña - ID: ' . $campana->id . ', Título: ' . $campana->titulo . ', Archivo: ' . $campana->archivo_path);
                }
            }

            // Si no hay imágenes válidas, no mostramos el carrusel
            if (empty($imagenes)) {
                $imagenes = [];
            }
        } else {
            // Si no hay campañas de tipo imagen, no mostramos el carrusel
            $imagenes = [];

            // No hay campaña seleccionada
            $campanaSeleccionada = null;
        }

        // Tiempo de visualización configurable (segundos)
        $tiempoVisualizacion = $zona->segundos ?? 15;

        // Registramos solo información mínima para producción
        if (count($imagenes) > 0) {
            \Log::info('Portal cautivo - Zona ID: ' . $zona->id . ' - ' . count($imagenes) . ' imágenes disponibles');
        } else {
            \Log::warning('Portal cautivo - Zona ID: ' . $zona->id . ' - No hay imágenes disponibles');
        }

        return view('zonas.preview-carrusel', compact('zona', 'mikrotikData', 'camposHtml', 'imagenes', 'campanaSeleccionada', 'tiempoVisualizacion', 'mostrarFormulario'));
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

        // Obtener campañas activas de la zona usando el método del modelo
        $campanasActivas = $zona->getCampanasActivas();

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

            // Asegurar que la campaña seleccionada se asocie correctamente con esta zona
            if ($campanaSeleccionada) {
                try {
                    // Verificar si la relación ya existe
                    if (!\DB::table('campana_zona')->where('zona_id', $zona->id)->where('campana_id', $campanaSeleccionada->id)->exists()) {
                        \Log::info("Asociando campaña seleccionada en previewVideo: Zona {$zona->id} con Campaña {$campanaSeleccionada->id}");
                        $zona->campanas()->attach($campanaSeleccionada->id);
                    }
                } catch (\Exception $e) {
                    \Log::error("Error al asociar campaña seleccionada con zona en previewVideo: " . $e->getMessage());
                }
            }

            // Obtener video de la campaña seleccionada
            if ($campanaSeleccionada && $campanaSeleccionada->archivo_path) {
                // Cargar el video almacenado en la campaña
                $videoUrl = Storage::url($campanaSeleccionada->archivo_path);
            } else {
                // Si no hay video en la campaña, no mostrar nada
                $videoUrl = '';
            }
        } else {
            // Si no hay campañas de tipo video, no mostrar ningún video
            $videoUrl = '';
        }

        // Verificar si la zona tiene tipo de registro "sin_registro" o si no tiene campos
        $mostrarFormulario = $zona->tipo_registro != 'sin_registro' && $zona->campos->count() > 0;

        return view('zonas.preview-video', compact('zona', 'mikrotikData', 'camposHtml', 'videoUrl', 'campanaSeleccionada', 'mostrarFormulario'));
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

        // Obtener campañas activas de la zona usando el método del modelo
        $campanasActivas = $zona->getCampanasActivas();

        // Filtrar campañas por tipo
        $campanasVideo = $campanasActivas->where('tipo', 'video');
        // Compatibilidad con 'imagen' e 'imagenes'
        $campanasImagenes = $campanasActivas->filter(function($campana) {
            return $campana->tipo === 'imagen' || $campana->tipo === 'imagenes';
        });

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

            // Asegurar que la campaña de video seleccionada se asocie correctamente con esta zona
            if ($campanaSeleccionada) {
                try {
                    // Verificar si la relación ya existe
                    if (!\DB::table('campana_zona')->where('zona_id', $zona->id)->where('campana_id', $campanaSeleccionada->id)->exists()) {
                        \Log::info("Asociando campaña de video en previewCampana: Zona {$zona->id} con Campaña {$campanaSeleccionada->id}");
                        $zona->campanas()->attach($campanaSeleccionada->id);
                    }
                } catch (\Exception $e) {
                    \Log::error("Error al asociar campaña de video con zona en previewCampana: " . $e->getMessage());
                }
            }

            // Preparar contenido de video
            if ($campanaSeleccionada && $campanaSeleccionada->archivo_path) {
                // Cargar el video almacenado en la campaña
                $contenido = [
                    'url' => Storage::url($campanaSeleccionada->archivo_path),
                    'poster' => '', // No usamos poster
                    'titulo' => $campanaSeleccionada->titulo ?? 'Video promocional'
                ];
            } else {
                // Si no hay video en la campaña, no mostrar nada
                $contenido = [
                    'url' => '',
                    'poster' => '',
                    'titulo' => ''
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

            // Asegurar que la campaña de imágenes seleccionada se asocie correctamente con esta zona
            if ($campanaSeleccionada) {
                try {
                    // Verificar si la relación ya existe
                    if (!\DB::table('campana_zona')->where('zona_id', $zona->id)->where('campana_id', $campanaSeleccionada->id)->exists()) {
                        \Log::info("Asociando campaña de imágenes en previewCampana: Zona {$zona->id} con Campaña {$campanaSeleccionada->id}");
                        $zona->campanas()->attach($campanaSeleccionada->id);
                    }
                } catch (\Exception $e) {
                    \Log::error("Error al asociar campaña de imágenes con zona en previewCampana: " . $e->getMessage());
                }
            }

            // Preparar contenido de imágenes
            if ($campanaSeleccionada && $campanaSeleccionada->archivo_path) {
                // Cargar la imagen de la campaña seleccionada
                $contenido = [
                    Storage::url($campanaSeleccionada->archivo_path)
                ];
            } else {
                // Si no hay imagen en la campaña, no mostramos nada
                $contenido = [];
            }
        } else {
            // No hay campañas activas - no mostrar contenido
            $tipoCampana = 'imagenes';
            $contenido = [];
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

        // Verificar si la zona tiene tipo de registro "sin_registro" o si no tiene campos
        $mostrarFormulario = $zona->tipo_registro != 'sin_registro' && $zona->campos->count() > 0;
         // Para depuración, eliminar en producción
        return view('zonas.preview-campana', compact(
            'zona',
            'mikrotikData',
            'camposHtml',
            'tipoCampana',
            'contenido',
            'campanaSeleccionada',
            'tiempoVisualizacion',
            'debugInfo',
            'mostrarFormulario'
        ));
    }
}
