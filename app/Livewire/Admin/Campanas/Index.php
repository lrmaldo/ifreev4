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
        } else {
            // Para creación o cuando se sube un nuevo archivo en edición
            $validacionArchivo = $this->tipo === 'imagen'
                ? ($this->editando ? 'nullable|image|max:2048' : 'required|image|max:2048')
                : ($this->editando ? 'nullable|mimes:mp4,mov,ogg,qt,webm,mpeg,avi|max:102400' : 'required|mimes:mp4,mov,ogg,qt,webm,mpeg,avi|max:102400');

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
            $this->showModal = true;

            // Despachar evento para inicializar el JavaScript con las zonas seleccionadas
            $this->dispatch('initializeZonasSelect', $this->zonas_ids);
        }
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
            $this->validate();

            // Subir archivo
            $archivoPath = $this->archivo_actual;

            if ($this->archivo) {
                // Si hay un archivo nuevo, eliminar el anterior si estamos editando
                if ($this->editando && $this->archivo_actual) {
                    Storage::disk('public')->delete($this->archivo_actual);
                }

                // Generar un nombre único para el archivo
                $extension = $this->archivo->getClientOriginalExtension();
                $nombreArchivo = time() . '_' . Str::random(10) . '.' . $extension;

                // Subir el archivo
                $carpeta = $this->tipo === 'imagen' ? 'campanas/imagenes' : 'campanas/videos';
                $archivoPath = $this->archivo->storeAs($carpeta, $nombreArchivo, 'public');
                
                // Log para debugging
                \Log::info('Archivo subido correctamente', [
                    'tipo' => $this->tipo,
                    'nombre_original' => $this->archivo->getClientOriginalName(),
                    'extension' => $extension,
                    'tamaño' => $this->archivo->getSize(),
                    'mime_type' => $this->archivo->getMimeType(),
                    'ruta' => $archivoPath
                ]);
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
            $campana->update($data);
            $this->sincronizarZonasInterno($campana);
            session()->flash('message', 'Campaña actualizada con éxito.');
        } else {
            $campana = Campana::create($data);
            $this->sincronizarZonasInterno($campana);
            session()->flash('message', 'Campaña creada con éxito.');
        }

        $this->closeModal();
        } catch (\Exception $e) {
            // Log el error
            \Log::error('Error al guardar campaña', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tipo' => $this->tipo,
                'tiene_archivo' => !empty($this->archivo),
                'editando' => $this->editando,
                'archivo_actual' => $this->archivo_actual
            ]);
            
            // Mostrar mensaje de error al usuario
            session()->flash('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    // Editar una campaña existente
    public function edit($id)
    {
        $this->editando = true;
        $this->campana_id = $id;

        $campana = Campana::find($id);
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

        // Asegurarnos que las zonas se cargan correctamente
        $this->zonas_ids = $campana->zonas->pluck('id')->toArray();

        $this->showModal = true;

        // Esperar a que el DOM se actualice antes de inicializar Select2
        $this->dispatch('initializeZonasSelect', $this->zonas_ids);

        // Para debugging
        $this->dispatch('consolelog', 'Zonas cargadas para edición: ' . implode(', ', $this->zonas_ids));
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
        if (empty($this->zonas_ids)) {
            $campana->zonas()->detach();
            return;
        }

        $user = Auth::user();

        // Si es admin, puede asignar cualquier zona
        if ($user->hasRole('admin')) {
            $campana->zonas()->sync($this->zonas_ids);
            return;
        }

        // Para usuarios normales, verificar que sean propietarios de las zonas
        $zonasAutorizadas = Zona::where('user_id', $user->id)
                               ->whereIn('id', $this->zonas_ids)
                               ->pluck('id')
                               ->toArray();

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
}
