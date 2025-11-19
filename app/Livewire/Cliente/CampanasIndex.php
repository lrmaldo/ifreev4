<?php

namespace App\Livewire\Cliente;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Campana;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CampanasIndex extends Component
{
    use WithPagination, AuthorizesRequests;

    public $search = '';
    public $filtroTipo = '';
    public $filtroActivo = false;
    public $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo()
    {
        $this->resetPage();
    }

    public function updatingFiltroActivo()
    {
        $this->resetPage();
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
        }]);

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

        if ($this->filtroActivo) {
            $query->where('activo', true);
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.cliente.campanas-index', [
            'campanas' => $this->campanas,
        ]);
    }
}
