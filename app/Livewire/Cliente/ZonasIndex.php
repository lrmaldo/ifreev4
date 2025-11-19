<?php

namespace App\Livewire\Cliente;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Zona;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ZonasIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $selectedZona = null;
    public $showInstructionsModal = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function openInstructionsModal($zonaId)
    {
        $this->selectedZona = Zona::find($zonaId);
        $this->showInstructionsModal = true;
    }

    public function closeInstructionsModal()
    {
        $this->showInstructionsModal = false;
        $this->selectedZona = null;
    }

    public function downloadFile($zonaId, $fileType)
    {
        $zona = Zona::find($zonaId);

        // Verificar que el usuario sea propietario de la zona
        if (!$zona || $zona->user_id !== Auth::id()) {
            session()->flash('error', 'No tienes permisos para acceder a esta zona.');
            return;
        }

        // Redirigir a la ruta de descarga
        return redirect()->route('admin.zonas.download', [
            'zonaId' => $zonaId,
            'fileType' => $fileType
        ]);
    }

    public function render()
    {
        $zonas = Zona::where('user_id', Auth::id())
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            })
            ->with(['user', 'campanas'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.cliente.zonas-index', compact('zonas'));
    }
}
