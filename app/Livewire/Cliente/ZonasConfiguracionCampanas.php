<?php

namespace App\Livewire\Cliente;

use Livewire\Component;
use App\Models\Zona;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ZonasConfiguracionCampanas extends Component
{
    use AuthorizesRequests;

    public Zona $zona;
    public $seleccion_campanas;
    public $tiempo_visualizacion;

    public function mount($zonaId)
    {
        $this->zona = Zona::findOrFail($zonaId);

        // Verificar que el usuario sea propietario de la zona
        if ($this->zona->user_id !== auth()->id()) {
            abort(403, 'No tienes permisos para acceder a esta zona.');
        }

        $this->seleccion_campanas = $this->zona->seleccion_campanas ?: 'aleatorio';
        $this->tiempo_visualizacion = $this->zona->tiempo_visualizacion ?: 15;
    }

    public function save()
    {
        $this->validate([
            'seleccion_campanas' => 'required|in:aleatorio,prioridad,video,imagen',
            'tiempo_visualizacion' => 'required|integer|min:5|max:120',
        ]);

        // Verificar nuevamente que el usuario sea propietario
        if ($this->zona->user_id !== auth()->id()) {
            session()->flash('error', 'No tienes permisos para modificar esta zona.');
            return;
        }

        $this->zona->update([
            'seleccion_campanas' => $this->seleccion_campanas,
            'tiempo_visualizacion' => $this->tiempo_visualizacion,
        ]);

        session()->flash('message', 'Configuración de campañas actualizada correctamente.');
    }

    public function render()
    {
        return view('livewire.cliente.zonas-configuracion-campanas');
    }
}
