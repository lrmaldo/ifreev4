<?php

namespace App\Livewire\Cliente;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Campana;
use App\Models\Zona;
use App\Models\Cliente;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class CampanasIndex extends Component
{
    use WithPagination, AuthorizesRequests, WithFileUploads;

    public $search = '';
    public $filtroTipo = '';
    public $mostrarSoloActivas = false;
    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    // Modal properties
    public $showModal = false;
    public $editando = false;
    public $campanaId;

    // Form properties
    public $titulo = '';
    public $descripcion = '';
    public $enlace = '';
    public $fecha_inicio = '';
    public $fecha_fin = '';
    public $tipo = 'imagen';
    public $prioridad = 1;
    public $archivo;
    public $archivo_actual = '';
    public $visible = true;
    public $siempre_visible = false;
    public $dias_visibles = [];
    public $cliente_id = null;
    public $zonas_ids = [];

    // Zona search properties
    public $zonaSearch = '';
    public $mostrarDropdownZonas = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo()
    {
        $this->resetPage();
    }

    public function updatingMostrarSoloActivas()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->editando = false;
        $this->showModal = true;
        $this->cliente_id = auth()->user()->cliente_id; // Auto-asignar el cliente actual
    }

    public function edit($campanaId)
    {
        $campana = Campana::findOrFail($campanaId);

        // Verificar que el usuario tenga acceso a esta campaña
        $zonasIds = auth()->user()->zonas->pluck('id');
        $tieneAcceso = $campana->zonas->pluck('id')->intersect($zonasIds)->count() > 0;

        if (!$tieneAcceso) {
            session()->flash('error', 'No tienes permisos para editar esta campaña');
            return;
        }

        $this->campanaId = $campana->id;
        $this->titulo = $campana->titulo;
        $this->descripcion = $campana->descripcion;
        $this->enlace = $campana->enlace;
        $this->fecha_inicio = $campana->fecha_inicio ? $campana->fecha_inicio->format('Y-m-d') : '';
        $this->fecha_fin = $campana->fecha_fin ? $campana->fecha_fin->format('Y-m-d') : '';
        $this->tipo = $campana->tipo;
        $this->prioridad = $campana->prioridad ?? 1;
        $this->archivo_actual = $campana->archivo_path;
        $this->visible = $campana->visible;
        $this->siempre_visible = $campana->siempre_visible;
        $this->dias_visibles = $campana->dias_visibles ? explode(',', $campana->dias_visibles) : [];
        $this->cliente_id = $campana->cliente_id;
        $this->zonas_ids = $campana->zonas->pluck('id')->toArray();

        $this->editando = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'enlace' => 'nullable|url',
            'tipo' => 'required|in:imagen,video',
            'prioridad' => 'nullable|integer|min:1|max:100',
            'visible' => 'boolean',
            'siempre_visible' => 'boolean',
            'zonas_ids' => 'required|array|min:1',
            'zonas_ids.*' => 'exists:zonas,id',
        ];

        if (!$this->siempre_visible) {
            $rules['fecha_inicio'] = 'required|date';
            $rules['fecha_fin'] = 'required|date|after_or_equal:fecha_inicio';
        }

        if ($this->archivo) {
            if ($this->tipo === 'imagen') {
                $rules['archivo'] = 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240'; // 10MB
            } else {
                $rules['archivo'] = 'required|file|mimes:mp4,mov,avi,wmv,flv,webm,mkv,m4v,3gp,ogg,qt,mpeg|max:102400'; // 100MB
            }
        } elseif (!$this->editando) {
            $rules['archivo'] = 'required';
        }

        $this->validate($rules);

        // Verificar que las zonas pertenezcan al usuario
        $zonasUsuario = auth()->user()->zonas->pluck('id');
        $zonasInvalidas = collect($this->zonas_ids)->diff($zonasUsuario);

        if ($zonasInvalidas->count() > 0) {
            session()->flash('error', 'No tienes permisos para asignar campaña a algunas de las zonas seleccionadas');
            return;
        }

        try {
            if ($this->editando) {
                $campana = Campana::findOrFail($this->campanaId);
            } else {
                $campana = new Campana();
            }

            $campana->titulo = $this->titulo;
            $campana->descripcion = $this->descripcion;
            $campana->enlace = $this->enlace;
            $campana->tipo = $this->tipo;
            $campana->prioridad = $this->prioridad;
            $campana->visible = $this->visible;
            $campana->siempre_visible = $this->siempre_visible;
            $campana->cliente_id = auth()->user()->cliente_id;

            if (!$this->siempre_visible) {
                $campana->fecha_inicio = $this->fecha_inicio;
                $campana->fecha_fin = $this->fecha_fin;
            } else {
                $campana->fecha_inicio = null;
                $campana->fecha_fin = null;
            }

            $campana->dias_visibles = !empty($this->dias_visibles) ? implode(',', $this->dias_visibles) : null;

            if ($this->archivo) {
                // Eliminar archivo anterior si existe
                if ($campana->archivo_path && Storage::exists($campana->archivo_path)) {
                    Storage::delete($campana->archivo_path);
                }

                $path = $this->archivo->store('campanas', 'public');
                $campana->archivo_path = $path;
            }

            $campana->save();

            // Sincronizar zonas
            $campana->zonas()->sync($this->zonas_ids);

            session()->flash('message', $this->editando ? 'Campaña actualizada exitosamente' : 'Campaña creada exitosamente');
            $this->closeModal();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar la campaña: ' . $e->getMessage());
        }
    }

    public function delete($campanaId)
    {
        $campana = Campana::findOrFail($campanaId);

        // Verificar que el usuario tenga acceso a esta campaña
        $zonasIds = auth()->user()->zonas->pluck('id');
        $tieneAcceso = $campana->zonas->pluck('id')->intersect($zonasIds)->count() > 0;

        if (!$tieneAcceso) {
            session()->flash('error', 'No tienes permisos para eliminar esta campaña');
            return;
        }

        // Eliminar archivo si existe
        if ($campana->archivo_path && Storage::exists($campana->archivo_path)) {
            Storage::delete($campana->archivo_path);
        }

        $campana->delete();
        session()->flash('message', 'Campaña eliminada exitosamente');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->titulo = '';
        $this->descripcion = '';
        $this->enlace = '';
        $this->fecha_inicio = '';
        $this->fecha_fin = '';
        $this->tipo = 'imagen';
        $this->prioridad = 1;
        $this->archivo = null;
        $this->archivo_actual = '';
        $this->visible = true;
        $this->siempre_visible = false;
        $this->dias_visibles = [];
        $this->cliente_id = null;
        $this->zonas_ids = [];
        $this->zonaSearch = '';
        $this->mostrarDropdownZonas = false;
        $this->campanaId = null;
    }

    public function toggleVisibility($campanaId)
    {
        $campana = Campana::findOrFail($campanaId);

        // Verificar que el usuario tenga acceso a esta campaña
        $zonasIds = auth()->user()->zonas->pluck('id');
        $tieneAcceso = $campana->zonas->pluck('id')->intersect($zonasIds)->count() > 0;

        if (!$tieneAcceso) {
            session()->flash('error', 'No tienes permisos para modificar esta campaña');
            return;
        }

        $campana->visible = !$campana->visible;
        $campana->save();

        $estado = $campana->visible ? 'visible' : 'oculta';
        session()->flash('message', "Campaña marcada como {$estado}");
    }

    // Métodos para el selector de zonas
    public function toggleZona($zonaId)
    {
        if (in_array($zonaId, $this->zonas_ids)) {
            $this->zonas_ids = array_values(array_diff($this->zonas_ids, [$zonaId]));
        } else {
            $this->zonas_ids[] = $zonaId;
        }
    }

    public function cerrarDropdownZonas()
    {
        $this->mostrarDropdownZonas = false;
    }

    public function getZonasFiltradas()
    {
        $zonasUsuario = auth()->user()->zonas();

        if ($this->zonaSearch) {
            return $zonasUsuario->where('nombre', 'like', '%' . $this->zonaSearch . '%')->get();
        }

        return $zonasUsuario->get();
    }

    public function getZonas()
    {
        return auth()->user()->zonas;
    }

    public function getClientes()
    {
        // Para clientes, solo retornar vacío ya que no pueden seleccionar cliente
        return collect();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortBy = $field;
    }

    public function toggleActivo($campanaId)
    {
        $campana = Campana::findOrFail($campanaId);

        // Verificar que el usuario tenga acceso a esta campaña (debe estar en sus zonas)
        $zonasIds = auth()->user()->zonas->pluck('id');
        $tieneAcceso = $campana->zonas->pluck('id')->intersect($zonasIds)->count() > 0;

        if (!$tieneAcceso) {
            session()->flash('error', 'No tienes permisos para modificar esta campaña');
            return;
        }

        $campana->activo = !$campana->activo;
        $campana->save();

        $estado = $campana->activo ? 'activada' : 'desactivada';
        session()->flash('message', "Campaña {$estado} exitosamente");
    }

    public function getCampanasProperty()
    {
        $user = auth()->user();

        // Obtener IDs de las zonas del usuario
        $zonasIds = $user->zonas->pluck('id');

        // Filtrar campañas que están asociadas a las zonas del usuario
        $query = Campana::whereHas('zonas', function($q) use ($zonasIds) {
            $q->whereIn('zonas.id', $zonasIds);
        })->with(['zonas' => function($q) use ($zonasIds) {
            // Solo cargar las zonas que pertenecen al usuario
            $q->whereIn('zonas.id', $zonasIds);
        }, 'cliente']);

        // Aplicar filtros
        if ($this->search) {
            $query->where(function($q) {
                $q->where('titulo', 'like', '%' . $this->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filtroTipo) {
            $query->where('tipo', $this->filtroTipo);
        }

        if ($this->mostrarSoloActivas) {
            $query->where('visible', true);
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
                    ->paginate($this->perPage);
    }

    public function getZonasFiltradasProperty()
    {
        return $this->getZonasFiltradas();
    }

    public function getZonasProperty()
    {
        return $this->getZonas();
    }

    public function getClientesProperty()
    {
        return $this->getClientes();
    }

    public function render()
    {
        return view('livewire.cliente.campanas-index', [
            'campanas' => $this->campanas,
        ]);
    }
}
