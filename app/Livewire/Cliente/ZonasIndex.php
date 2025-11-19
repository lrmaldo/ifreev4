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

    // Modal properties
    public $showModal = false;
    public $editMode = false;
    public $zonaId;
    public $confirmingZonaDeletion = false;

    // Form properties
    public $nombre = '';
    public $descripcion = '';
    public $id_personalizado = '';
    public $tipo_registro = 'sin_registro';
    public $segundos = 10;
    public $tiempo_visualizacion = 15;
    public $seleccion_campanas = 'aleatorio';
    public $tipo_autenticacion_mikrotik = 'sin_autenticacion';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        // Verificar que el usuario sea propietario de la zona
        if ($zona->user_id !== Auth::id()) {
            session()->flash('error', 'No tienes permisos para editar esta zona.');
            return;
        }

        $this->zonaId = $zona->id;
        $this->nombre = $zona->nombre;
        $this->descripcion = $zona->descripcion;
        $this->id_personalizado = $zona->id_personalizado;
        $this->tipo_registro = $zona->tipo_registro;
        $this->segundos = $zona->segundos;
        $this->tiempo_visualizacion = $zona->tiempo_visualizacion ?? 15;
        $this->seleccion_campanas = $zona->seleccion_campanas;
        $this->tipo_autenticacion_mikrotik = $zona->tipo_autenticacion_mikrotik;

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'id_personalizado' => 'nullable|string|max:255|unique:zonas,id_personalizado,' . ($this->editMode ? $this->zonaId : ''),
            'tipo_registro' => 'required|in:sin_registro,registro_simple,registro_completo',
            'segundos' => 'required|integer|min:1|max:300',
            'tiempo_visualizacion' => 'nullable|integer|min:5|max:300',
            'seleccion_campanas' => 'required|in:aleatorio,prioridad,video,imagen',
            'tipo_autenticacion_mikrotik' => 'required|in:sin_autenticacion,pin,usuario_password',
        ];

        $this->validate($rules);

        try {
            if ($this->editMode) {
                $zona = Zona::findOrFail($this->zonaId);

                // Verificar que el usuario sea propietario
                if ($zona->user_id !== Auth::id()) {
                    session()->flash('error', 'No tienes permisos para modificar esta zona.');
                    return;
                }
            } else {
                $zona = new Zona();
                $zona->user_id = Auth::id();
            }

            $zona->nombre = $this->nombre;
            $zona->descripcion = $this->descripcion;
            $zona->id_personalizado = $this->id_personalizado;
            $zona->tipo_registro = $this->tipo_registro;
            $zona->segundos = $this->segundos;
            $zona->tiempo_visualizacion = $this->tiempo_visualizacion;
            $zona->seleccion_campanas = $this->seleccion_campanas;
            $zona->tipo_autenticacion_mikrotik = $this->tipo_autenticacion_mikrotik;

            $zona->save();

            session()->flash('message', $this->editMode ? 'Zona actualizada exitosamente' : 'Zona creada exitosamente');
            $this->closeModal();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar la zona: ' . $e->getMessage());
        }
    }

    public function confirmDelete($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        // Verificar que el usuario sea propietario de la zona
        if ($zona->user_id !== Auth::id()) {
            session()->flash('error', 'No tienes permisos para eliminar esta zona.');
            return;
        }

        $this->zonaId = $zonaId;
        $this->confirmingZonaDeletion = true;
    }

    public function deleteZona()
    {
        $zona = Zona::findOrFail($this->zonaId);

        // Verificar que el usuario sea propietario
        if ($zona->user_id !== Auth::id()) {
            session()->flash('error', 'No tienes permisos para eliminar esta zona.');
            return;
        }

        $zona->delete();

        $this->confirmingZonaDeletion = false;
        session()->flash('message', 'Zona eliminada exitosamente');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->nombre = '';
        $this->descripcion = '';
        $this->id_personalizado = '';
        $this->tipo_registro = 'sin_registro';
        $this->segundos = 10;
        $this->tiempo_visualizacion = 15;
        $this->seleccion_campanas = 'aleatorio';
        $this->tipo_autenticacion_mikrotik = 'sin_autenticacion';
        $this->zonaId = null;
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
