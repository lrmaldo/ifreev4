<?php

namespace App\Livewire\Cliente;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Zona;
use App\Models\Campana;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class ConfiguracionCampanas extends Component
{
    use WithFileUploads, AuthorizesRequests;

    public $zonaId;
    public $zona;
    public $campanasDisponibles;
    public $campanasAsociadas;

    // Formulario para nueva campaña
    public $showCrearCampana = false;
    public $titulo = '';
    public $descripcion = '';
    public $tipo = 'imagen';
    public $archivo;
    public $enlace = '';
    public $prioridad = 1;
    public $activo = true;

    public function mount($zonaId)
    {
        $this->zonaId = $zonaId;
        $this->loadZona();
        $this->loadCampanas();
    }

    public function loadZona()
    {
        $this->zona = Zona::findOrFail($this->zonaId);

        // Verificar que el usuario tenga acceso a esta zona
        if (!auth()->user()->hasRole('admin') && $this->zona->user_id !== auth()->id()) {
            abort(403, 'No tienes permisos para acceder a esta zona');
        }
    }

    public function loadCampanas()
    {
        // Campañas ya asociadas a esta zona
        $this->campanasAsociadas = $this->zona->campanas()->get();

        // Todas las campañas disponibles (del usuario o del admin)
        if (auth()->user()->hasRole('admin')) {
            $this->campanasDisponibles = Campana::whereNotIn('id', $this->campanasAsociadas->pluck('id'))
                                               ->orderBy('titulo')
                                               ->get();
        } else {
            // Campañas del usuario (que están en sus zonas) y que no están ya asociadas a esta zona
            $zonasUsuario = auth()->user()->zonas->pluck('id');
            $this->campanasDisponibles = Campana::whereHas('zonas', function($q) use ($zonasUsuario) {
                $q->whereIn('zonas.id', $zonasUsuario);
            })->whereNotIn('id', $this->campanasAsociadas->pluck('id'))
              ->distinct()
              ->orderBy('titulo')
              ->get();
        }
    }

    public function asociarCampana($campanaId)
    {
        try {
            $campana = Campana::findOrFail($campanaId);

            // Verificar permisos
            if (!auth()->user()->hasRole('admin')) {
                $zonasUsuario = auth()->user()->zonas->pluck('id');
                if (!$campana->zonas->pluck('id')->intersect($zonasUsuario)->count()) {
                    $this->dispatch('error', 'No tienes permisos para asociar esta campaña');
                    return;
                }
            }

            // Asociar campaña a la zona
            if (!$this->zona->campanas()->where('campana_id', $campanaId)->exists()) {
                $this->zona->campanas()->attach($campanaId);
                $this->dispatch('success', 'Campaña asociada exitosamente');
                $this->loadCampanas();
            }
        } catch (\Exception $e) {
            $this->dispatch('error', 'Error al asociar la campaña');
        }
    }

    public function desasociarCampana($campanaId)
    {
        try {
            $this->zona->campanas()->detach($campanaId);
            $this->dispatch('success', 'Campaña desasociada exitosamente');
            $this->loadCampanas();
        } catch (\Exception $e) {
            $this->dispatch('error', 'Error al desasociar la campaña');
        }
    }

    public function toggleActivoCampana($campanaId)
    {
        try {
            $campana = Campana::findOrFail($campanaId);

            // Verificar permisos
            if (!auth()->user()->hasRole('admin')) {
                $zonasUsuario = auth()->user()->zonas->pluck('id');
                if (!$campana->zonas->pluck('id')->intersect($zonasUsuario)->count()) {
                    $this->dispatch('error', 'No tienes permisos para modificar esta campaña');
                    return;
                }
            }

            $campana->activo = !$campana->activo;
            $campana->save();

            $estado = $campana->activo ? 'activada' : 'desactivada';
            $this->dispatch('success', "Campaña {$estado} exitosamente");
            $this->loadCampanas();
        } catch (\Exception $e) {
            $this->dispatch('error', 'Error al cambiar el estado de la campaña');
        }
    }

    public function crearCampana()
    {
        $this->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:imagen,video',
            'archivo' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:51200', // 50MB
            'enlace' => 'nullable|url',
            'prioridad' => 'required|integer|min:1|max:100',
        ]);

        try {
            // Subir archivo
            $path = $this->archivo->store('campanas', 'public');

            // Crear campaña
            $campana = Campana::create([
                'titulo' => $this->titulo,
                'descripcion' => $this->descripcion,
                'tipo' => $this->tipo,
                'archivo_path' => $path,
                'enlace' => $this->enlace,
                'prioridad' => $this->prioridad,
                'activo' => $this->activo,
            ]);

            // Asociar automáticamente a la zona actual
            $this->zona->campanas()->attach($campana->id);

            $this->dispatch('success', 'Campaña creada y asociada exitosamente');
            $this->resetForm();
            $this->loadCampanas();
        } catch (\Exception $e) {
            $this->dispatch('error', 'Error al crear la campaña: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->reset([
            'titulo', 'descripcion', 'tipo', 'archivo',
            'enlace', 'prioridad', 'activo', 'showCrearCampana'
        ]);
    }

    public function render()
    {
        return view('livewire.cliente.configuracion-campanas');
    }
}
