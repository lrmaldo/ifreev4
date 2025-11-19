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
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortBy = $field;
        $this->resetPage();
    }

    public function getCampanasProperty()
    {
        $user = auth()->user();
        
        // Si es admin, puede ver todas las campañas
        if ($user->hasRole('admin')) {
            $query = Campana::query();
        } else {
            // Si es cliente, solo ve sus campañas (las de sus zonas)
            $zonasIds = $user->zonas->pluck('id');
            $query = Campana::whereHas('zonas', function($q) use ($zonasIds) {
                $q->whereIn('zonas.id', $zonasIds);
            });
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('titulo', 'like', '%' . $this->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $this->search . '%')
                  ->orWhere('tipo', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
                    ->paginate($this->perPage);
    }

    public function toggleActivo($campanaId)
    {
        $campana = Campana::findOrFail($campanaId);
        
        // Verificar que el usuario tenga acceso a esta campaña
        if (!auth()->user()->hasRole('admin')) {
            $zonasIds = auth()->user()->zonas->pluck('id');
            if (!$campana->zonas->pluck('id')->intersect($zonasIds)->count()) {
                $this->dispatch('error', 'No tienes permisos para modificar esta campaña');
                return;
            }
        }

        $campana->activo = !$campana->activo;
        $campana->save();

        $estado = $campana->activo ? 'activada' : 'desactivada';
        $this->dispatch('success', "Campaña {$estado} exitosamente");
    }

    public function render()
    {
        return view('livewire.cliente.campanas-index', [
            'campanas' => $this->campanas,
        ]);
    }
}
