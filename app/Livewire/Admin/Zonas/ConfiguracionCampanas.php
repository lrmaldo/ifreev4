<?php

namespace App\Livewire\Admin\Zonas;

use App\Models\Zona;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ConfiguracionCampanas extends Component
{
    public $zona;
    public $zonaId;
    public $seleccion_campanas;
    public $tiempo_visualizacion;

    protected $rules = [
        'seleccion_campanas' => 'required|in:aleatorio,prioridad,video,imagen',
        'tiempo_visualizacion' => 'required|integer|min:5|max:120'
    ];

    public function mount($zonaId)
    {
        $this->zonaId = $zonaId;
        $this->zona = Zona::findOrFail($zonaId);

        // Verificar permisos
        if (!Auth::user()->hasRole('admin') && $this->zona->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para acceder a esta zona.');
        }

        // Asignar valores iniciales
        $this->seleccion_campanas = $this->zona->seleccion_campanas ?? 'prioridad';
        $this->tiempo_visualizacion = $this->zona->tiempo_visualizacion ?? 15;
    }

    public function save()
    {
        $this->validate();

        $this->zona->update([
            'seleccion_campanas' => $this->seleccion_campanas,
            'tiempo_visualizacion' => $this->tiempo_visualizacion
        ]);

        session()->flash('message', 'Configuración de campañas actualizada correctamente.');
    }

    public function render()
    {
        return view('livewire.admin.zonas.configuracion-campanas', [
            'zona' => $this->zona
        ]);
    }
}
