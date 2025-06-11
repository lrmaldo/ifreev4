<?php

namespace App\Livewire\Admin\Campanas;

use App\Models\Campana;
use App\Models\Cliente;
use App\Models\Zona;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Index extends Component
{
    use WithPagination;
    use WithFileUploads;

    // Propiedades para el formulario
    public $titulo;
    public $descripcion;
    public $enlace;
    public $fecha_inicio;
    public $fecha_fin;
    public $visible = true;
    public $siempre_visible = false;
    public $dias_visibles = [];
    public $tipo = 'imagen';
    public $archivo;
    public $cliente_id;
    public $zonas_ids = [];
    public $prioridad = 10;

    // Propiedades para edición
    public $campana_id;
    public $editando = false;
    public $archivo_actual;

    // Filtros
    public $search = '';
    public $filtroTipo = '';
    public $mostrarSoloActivas = false;

    // Modal
    public $showModal = false;

    // Listeners para eventos de JavaScript
    protected $listeners = ['zonasActualizadas' => 'actualizarZonasJavaScript'];

    // Reglas de validación
    protected function rules()
    {
        $rules = [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'enlace' => 'nullable|url',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'visible' => 'boolean',
            'siempre_visible' => 'boolean',
            'dias_visibles' => 'nullable|array',
            'dias_visibles.*' => 'integer|between:0,6',
            'tipo' => 'required|in:imagen,video',
            'cliente_id' => 'nullable|exists:clientes,id',
            'zonas_ids' => 'nullable|array',
            'zonas_ids.*' => 'integer|exists:zonas,id',
            'prioridad' => 'required|integer|min:1|max:100',
        ];

        // Ajustar validación para el archivo
        if ($this->editando && !$this->archivo) {
            // Si estamos editando y no se ha subido un nuevo archivo, no validamos
            // porque mantendremos el archivo existente
            \Log::info('No validando archivo en edición sin nuevo archivo');
        } else {
            // Para creación o cuando se sube un nuevo archivo en edición
            if ($this->tipo === 'imagen') {
                $validacionArchivo = $this->editando
                    ? 'nullable|image|max:2048'
                    : 'required|image|max:2048';

                \Log::info('Validando imagen', [
                    'reglas' => $validacionArchivo,
                    'editando' => $this->editando,
                    'archivo_presente' => !empty($this->archivo)
                ]);
            } else {
                // Para videos
                $validacionArchivo = $this->editando
                    ? 'nullable|mimes:mp4,mov,ogg,qt,webm,mpeg,avi|max:102400'
                    : 'required|mimes:mp4,mov,ogg,qt,webm,mpeg,avi|max:102400';

                \Log::info('Validando video', [
                    'reglas' => $validacionArchivo,
                    'editando' => $this->editando,
                    'archivo_presente' => !empty($this->archivo)
                ]);
            }

            $rules['archivo'] = $validacionArchivo;
        }

        return $rules;
    }

    // Reiniciar propiedades del formulario
    public function resetForm()
    {
        $this->reset([
            'titulo', 'descripcion', 'enlace', 'fecha_inicio', 'fecha_fin',
            'visible', 'siempre_visible', 'dias_visibles', 'tipo', 'archivo',
            'cliente_id', 'campana_id', 'editando', 'archivo_actual', 'zonas_ids',
            'prioridad'
        ]);

        // Valores por defecto
        $this->visible = true;
        $this->siempre_visible = false;
        $this->dias_visibles = [];
        $this->tipo = 'imagen';
        $this->prioridad = 10;
        $this->fecha_inicio = now()->format('Y-m-d');
        $this->fecha_fin = now()->addDays(30)->format('Y-m-d');
    }

    #[On('openCampanaModal')]
    public function openModal()
    {
        // Solo reset si no estamos editando (para no sobreescribir datos de edición)
        if (!$this->editando) {
            $this->resetForm();
        }

        $this->showModal = true;
    }

    // Cerrar el modal
    #[On('closeModal')]
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    // Guardar la campaña
    public function save()
    {
        try {
            \Log::info('Iniciando guardado de campaña en modo producción', [
                'tipo' => $this->tipo,
                'editando' => $this->editando,
                'tiene_archivo' => !empty($this->archivo),
                'archivo_actual' => $this->archivo_actual
            ]);

            // Validar primero sin el archivo para depurar problemas
            $rules = $this->rules();
            if (isset($rules['archivo'])) {
                $archivoRule = $rules['archivo'];
                unset($rules['archivo']);
            }

            // Validar todo excepto el archivo primero
            $this->validate($rules);
            \Log::info('Validación de datos básicos completada');

            // Luego validar el archivo si es necesario
            if (isset($archivoRule) && $this->archivo) {
                $this->validate(['archivo' => $archivoRule]);
                \Log::info('Validación de archivo completada');
            }

            // Subir archivo
            $archivoPath = $this->archivo_actual;

            if ($this->archivo) {
                try {
                    \Log::info('Procesando archivo', [
                        'nombre_original' => $this->archivo->getClientOriginalName(),
                        'tamaño' => $this->archivo->getSize(),
                        'mime_type' => $this->archivo->getMimeType()
                    ]);

                    // Si hay un archivo nuevo, eliminar el anterior si estamos editando
                    if ($this->editando && $this->archivo_actual) {
                        Storage::disk('public')->delete($this->archivo_actual);
                        \Log::info('Archivo anterior eliminado');
                    }

                    // Generar un nombre único para el archivo
                    $extension = $this->archivo->getClientOriginalExtension();
                    $nombreArchivo = time() . '_' . Str::random(10) . '.' . $extension;

                    // Subir el archivo
                    $carpeta = $this->tipo === 'imagen' ? 'campanas/imagenes' : 'campanas/videos';

                    // Verificar que las carpetas existan y crearlas si no
                    $fullPath = 'public/' . $carpeta;
                    if (!Storage::exists($fullPath)) {
                        Storage::makeDirectory($fullPath);
                        \Log::info("Directorio creado: $fullPath");
                    }

                    // Verificar permisos antes de subir
                    $storagePath = storage_path('app/' . $fullPath);
                    \Log::info('Verificando permisos de directorio', [
                        'path' => $storagePath,
                        'existe' => file_exists($storagePath) ? 'Sí' : 'No',
                        'es_escribible' => is_writable($storagePath) ? 'Sí' : 'No',
                        'permisos' => file_exists($storagePath) ? substr(sprintf('%o', fileperms($storagePath)), -4) : 'N/A'
                    ]);

                    // Intentar guardar archivo con gestión de errores más detallada
                    $archivoPath = $this->archivo->storeAs($carpeta, $nombreArchivo, 'public');

                    if (!$archivoPath) {
                        throw new \Exception("Error al subir el archivo. No se pudo guardar en el almacenamiento.");
                    }

                    \Log::info('Archivo subido correctamente', [
                        'ruta' => $archivoPath
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error en la subida del archivo', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            } elseif (!$this->editando || !$this->archivo_actual) {
                // Si no hay archivo y no estamos editando, o estamos editando pero no hay archivo actual
                throw new \Exception("Se requiere un archivo " . ($this->tipo === 'imagen' ? 'de imagen' : 'de video'));
            }

            // Crear o actualizar la campaña
            $data = [
                'titulo' => $this->titulo,
                'descripcion' => $this->descripcion,
                'enlace' => $this->enlace,
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin' => $this->fecha_fin,
                'visible' => $this->visible,
                'siempre_visible' => $this->siempre_visible,
                'dias_visibles' => !empty($this->dias_visibles) ? $this->dias_visibles : null,
                'tipo' => $this->tipo,
                'archivo_path' => $archivoPath,
                'cliente_id' => $this->cliente_id,
                'prioridad' => $this->prioridad,
            ];

            if ($this->editando) {
                $campana = Campana::find($this->campana_id);
                if (!$campana) {
                    throw new \Exception("No se encontró la campaña a editar");
                }

                $campana->update($data);
                \Log::info('Campaña actualizada con éxito', ['id' => $campana->id]);

                $this->sincronizarZonasInterno($campana);
                session()->flash('message', 'Campaña actualizada con éxito.');
            } else {
                $campana = Campana::create($data);
                \Log::info('Nueva campaña creada con éxito', ['id' => $campana->id]);

                $this->sincronizarZonasInterno($campana);
                session()->flash('message', 'Campaña creada con éxito.');
            }

            $this->closeModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación al guardar campaña', [
                'errores' => $e->validator->errors()->toArray()
            ]);
            throw $e; // Mantener la excepción para que Livewire muestre los errores

        } catch (\Exception $e) {
            \Log::error('Error general al guardar campaña', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    // Editar una campaña existente
    public function edit($id)
    {
        $this->editando = true;
        $this->campana_id = $id;

        $campana = Campana::with('zonas')->find($id);
        $this->titulo = $campana->titulo;
        $this->descripcion = $campana->descripcion;
        $this->enlace = $campana->enlace;
        $this->fecha_inicio = $campana->fecha_inicio->format('Y-m-d');
        $this->fecha_fin = $campana->fecha_fin->format('Y-m-d');
        $this->visible = $campana->visible;
        $this->siempre_visible = $campana->siempre_visible;
        $this->dias_visibles = $campana->dias_visibles ?: [];
        $this->tipo = $campana->tipo;
        $this->archivo_actual = $campana->archivo_path;
        $this->cliente_id = $campana->cliente_id;
        $this->prioridad = $campana->prioridad;

        // Cargar zonas relacionadas
        $zonas_raw = $campana->zonas->pluck('id')->toArray();
        $this->zonas_ids = array_map('intval', $zonas_raw);

        // Log simplificado para debugging
        \Log::debug('Editando campaña', [
            'campana_id' => $id,
            'zonas_ids' => $this->zonas_ids,
            'total_zonas' => count($this->zonas_ids)
        ]);

        $this->showModal = true;

        // Despachar evento simplificado para Select2
        $this->dispatch('campanEditLoaded', [
            'zonasIds' => $this->zonas_ids,
            'campanaTitulo' => $this->titulo
        ]);
    }

    // Eliminar una campaña
    public function delete($id)
    {
        $campana = Campana::find($id);

        // Eliminar archivo asociado
        if ($campana->archivo_path) {
            Storage::disk('public')->delete($campana->archivo_path);
        }

        $campana->delete();
        session()->flash('message', 'Campaña eliminada con éxito.');
    }

    // Actualizar visibilidad rápidamente
    public function toggleVisibility($id)
    {
        $campana = Campana::find($id);
        $campana->visible = !$campana->visible;
        $campana->save();
    }

    public function updatedTipo()
    {
        // Reiniciamos el archivo cuando cambia el tipo para evitar validaciones cruzadas
        $this->reset('archivo');

        // Emitimos un evento para informar que el tipo ha cambiado
        $this->dispatch('tipo-changed', tipo: $this->tipo);
    }

    /**
     * Se ejecuta cuando cambia el valor de siempre_visible
     */
    public function updatedSiempreVisible()
    {
        // Si es siempre visible, no necesitamos días específicos
        if ($this->siempre_visible) {
            $this->dias_visibles = [];
        }
    }

    public function mount()
    {
        $this->fecha_inicio = now()->format('Y-m-d');
        $this->fecha_fin = now()->addDays(30)->format('Y-m-d');
    }

    public function render()
    {
        $query = Campana::query();

        if ($this->search) {
            $query->where('titulo', 'like', '%' . $this->search . '%')
                 ->orWhere('descripcion', 'like', '%' . $this->search . '%');
        }

        if ($this->filtroTipo) {
            $query->where('tipo', $this->filtroTipo);
        }

        if ($this->mostrarSoloActivas) {
            $query->activas();
        }

        $campanas = $query->latest()->paginate(10);
        $clientes = Cliente::orderBy('razon social')->get();
        $zonas = $this->getZonasDisponibles();

        return view('livewire.admin.campanas.index', [
            'campanas' => $campanas,
            'clientes' => $clientes,
            'zonas' => $zonas
        ]);
    }

    /**
     * Método público para sincronizar zonas desde JavaScript/Select2
     */
    public function sincronizarZonas($zonasIds)
    {
        // Filtrar valores vacíos y asegurarse que son enteros
        $this->zonas_ids = array_map('intval', array_filter($zonasIds));

        // Log para debugging
        \Log::info('sincronizarZonas llamado', [
            'zonasIds_recibidas' => $zonasIds,
            'zonas_ids_procesadas' => $this->zonas_ids,
            'editando' => $this->editando,
            'campana_id' => $this->campana_id
        ]);

        // Despachar evento para actualizar el JavaScript con los nuevos valores
        $this->dispatch('zonasActualizadas', $this->zonas_ids);

        // No renderizar de nuevo para evitar perder la selección
        $this->skipRender();
    }

    /**
     * Actualiza el atributo data-livewire-values en el elemento select del JavaScript
     */
    public function actualizarZonasJavaScript()
    {
        // Asegurar que zonas_ids es un array y convertir a enteros
        $zonas_ids = array_map('intval', array_filter($this->zonas_ids ?? []));

        // Despachar evento para actualizar el atributo en el select
        $this->dispatch('updateLivewireAttribute', [
            'elementId' => 'zonas_select',
            'attribute' => 'data-livewire-values',
            'value' => json_encode($zonas_ids)
        ]);

        // Para debugging
        $this->dispatch('consolelog', 'Zonas actualizadas: ' . implode(', ', $zonas_ids));

        // No renderizar de nuevo
        $this->skipRender();
    }

    /**
     * Sincroniza las zonas seleccionadas con la campaña
     * Valida que un usuario normal solo pueda asignar sus propias zonas
     */
    protected function sincronizarZonasInterno(Campana $campana)
    {
        // Log para debugging
        \Log::info('sincronizarZonasInterno llamado', [
            'campana_id' => $campana->id,
            'zonas_ids' => $this->zonas_ids,
            'zonas_ids_count' => count($this->zonas_ids ?? [])
        ]);

        if (empty($this->zonas_ids)) {
            \Log::info('Desvinculando todas las zonas de la campaña', ['campana_id' => $campana->id]);
            $campana->zonas()->detach();
            return;
        }

        $user = Auth::user();

        // Si es admin, puede asignar cualquier zona
        if ($user->hasRole('admin')) {
            \Log::info('Admin sincronizando zonas', [
                'campana_id' => $campana->id,
                'zonas_ids' => $this->zonas_ids
            ]);
            $campana->zonas()->sync($this->zonas_ids);
            return;
        }

        // Para usuarios normales, verificar que sean propietarios de las zonas
        $zonasAutorizadas = Zona::where('user_id', $user->id)
                               ->whereIn('id', $this->zonas_ids)
                               ->pluck('id')
                               ->toArray();

        \Log::info('Usuario normal sincronizando zonas', [
            'campana_id' => $campana->id,
            'zonas_solicitadas' => $this->zonas_ids,
            'zonas_autorizadas' => $zonasAutorizadas
        ]);

        $campana->zonas()->sync($zonasAutorizadas);

        // Si hay diferencia entre las zonas seleccionadas y las autorizadas, mostrar advertencia
        if (count($this->zonas_ids) !== count($zonasAutorizadas)) {
            session()->flash('warning', 'Algunas zonas no fueron asignadas porque no tienes permiso sobre ellas.');
        }
    }

    /**
     * Obtiene las zonas disponibles para el usuario actual
     */
    protected function getZonasDisponibles()
    {
        $user = Auth::user();

        // Si es admin, obtener todas las zonas
        if ($user->hasRole('admin')) {
            return Zona::orderBy('nombre')->get();
        }

        // Si es cliente, solo sus zonas
        return Zona::where('user_id', $user->id)->orderBy('nombre')->get();
    }

    /**
     * Método para diagnóstico de problemas de subida de archivos
     */
    public function diagnosticarProblemasArchivo()
    {
        try {
            LogFacade::info('---- DIAGNÓSTICO DE PROBLEMAS DE SUBIDA DE ARCHIVOS ----');

            // Información básica PHP
            LogFacade::info('Configuración PHP:', [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_input_time' => ini_get('max_input_time'),
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit')
            ]);

            // Verificar permisos de escritura
            $carpetas = [
                'storage_path' => storage_path(),
                'public_path' => public_path(),
                'storage_app_public' => storage_path('app/public'),
                'storage_app_public_campanas' => storage_path('app/public/campanas')
            ];

            foreach ($carpetas as $nombre => $ruta) {
                LogFacade::info('Verificando permisos carpeta: ' . $nombre, [
                    'ruta' => $ruta,
                    'existe' => file_exists($ruta) ? 'Sí' : 'No',
                    'es_escribible' => is_writable($ruta) ? 'Sí' : 'No',
                    'permisos' => file_exists($ruta) ? substr(sprintf('%o', fileperms($ruta)), -4) : 'N/A'
                ]);
            }

            // Verificar configuración Livewire
            $livewireConfig = config('livewire');
            LogFacade::info('Configuración Livewire para archivos:', [
                'temporary_file_upload_rules' => $livewireConfig['temporary_file_upload']['rules'] ?? 'No configurado',
                'temporary_file_upload_directory' => $livewireConfig['temporary_file_upload']['directory'] ?? 'Por defecto',
                'temporary_file_upload_max_time' => $livewireConfig['temporary_file_upload']['max_upload_time'] ?? 'Por defecto'
            ]);

            // Verificar discos de almacenamiento
            $filesystemConfig = config('filesystems');
            LogFacade::info('Configuración discos de almacenamiento:', [
                'default' => $filesystemConfig['default'] ?? 'No configurado',
                'public_disk' => array_key_exists('public', $filesystemConfig['disks']) ? 'Configurado' : 'No encontrado',
                'public_disk_root' => $filesystemConfig['disks']['public']['root'] ?? 'No configurado',
                'public_disk_url' => $filesystemConfig['disks']['public']['url'] ?? 'No configurado'
            ]);

            // Intentar crear directorios necesarios
            $directoriosNecesarios = [
                'campanas/imagenes',
                'campanas/videos'
            ];

            foreach ($directoriosNecesarios as $dir) {
                $fullPath = 'public/' . $dir;
                $exists = Storage::exists($fullPath);

                LogFacade::info('Verificando directorio ' . $dir, [
                    'existe' => $exists ? 'Sí' : 'No'
                ]);

                if (!$exists) {
                    Storage::makeDirectory($fullPath);
                    LogFacade::info('Directorio creado: ' . $fullPath);
                }
            }

            return 'El diagnóstico ha sido registrado en los logs. Por favor revisa storage/logs/laravel.log';
        } catch (\Exception $e) {
            LogFacade::error('Error durante el diagnóstico:', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 'Error durante el diagnóstico: ' . $e->getMessage();
        }
    }

    /**
     * Ejecutar diagnóstico y reparar problemas comunes
     */
    public function ejecutarDiagnostico()
    {
        try {
            // Registrar información de diagnóstico
            $resultadoDiagnostico = $this->diagnosticarProblemasArchivo();

            // Intentar crear los directorios necesarios
            $carpetas = ['campanas/imagenes', 'campanas/videos'];
            foreach ($carpetas as $carpeta) {
                $fullPath = 'public/' . $carpeta;
                if (!Storage::exists($fullPath)) {
                    Storage::makeDirectory($fullPath);
                    LogFacade::info('Directorio creado: ' . $fullPath);
                }
            }

            // Verificar el enlace simbólico de storage
            $publicPath = public_path('storage');
            $storagePath = storage_path('app/public');

            if (!file_exists($publicPath) || !is_link($publicPath)) {
                // Si estamos en Windows, el enlace simbólico puede ser complicado
                // En su lugar, verificamos si el directorio existe y tiene archivos
                if (PHP_OS_FAMILY === 'Windows') {
                    LogFacade::info('Sistema operativo Windows detectado');
                    if (!file_exists($publicPath) || !is_dir($publicPath)) {
                        LogFacade::warning('El directorio public/storage no existe. Se creará un directorio.');
                        if (!file_exists($publicPath)) {
                            @mkdir($publicPath, 0755, true);
                        }
                    }
                } else {
                    // En sistemas Unix podemos intentar crear el enlace simbólico
                    @symlink($storagePath, $publicPath);
                    LogFacade::info('Enlace simbólico creado de ' . $storagePath . ' a ' . $publicPath);
                }
            }

            // Verificar permisos de carpetas
            $carpetas = [
                storage_path('app/public'),
                storage_path('app/public/campanas'),
                storage_path('app/public/campanas/imagenes'),
                storage_path('app/public/campanas/videos')
            ];

            foreach ($carpetas as $carpeta) {
                if (!file_exists($carpeta)) {
                    @mkdir($carpeta, 0755, true);
                    LogFacade::info('Carpeta creada: ' . $carpeta);
                }

                // Intenta ajustar permisos
                @chmod($carpeta, 0755);
            }

            session()->flash('message', 'Diagnóstico completado. Se han realizado reparaciones automáticas. Revisa los logs para más detalles.');

            // Sugerir revisar el diagnóstico detallado
            return redirect(url('/diagnostico-archivos-livewire.php'));
        } catch (\Exception $e) {
            LogFacade::error('Error durante la reparación automática', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Error durante la reparación: ' . $e->getMessage());
        }
    }
}
