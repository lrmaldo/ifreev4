<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Zona;
use App\Traits\RenderizaFormFields;

class FormularioDinamico extends Component
{
    use RenderizaFormFields;

    public $zonaId;
    public $formulario = [];

    public function mount($zonaId = null)
    {
        $this->zonaId = $zonaId;
    }
      public function render()
    {
        $zona = $this->zonaId ? Zona::findOrFail($this->zonaId) : null;

        return view('livewire.formulario-dinamico', [
            'zona' => $zona,
            'campos' => $zona ? $zona->campos()->orderBy('orden')->get() : collect(),
        ]);
    }

    public function guardar()
    {
        // Si es necesario, puedes validar los campos obligatorios
        $zona = Zona::findOrFail($this->zonaId);
        $campos = $zona->campos()->where('obligatorio', true)->get();

        $reglas = [];

        foreach ($campos as $campo) {
            if ($campo->tipo != 'checkbox') {
                $reglas["formulario.{$campo->campo}"] = 'required';
            }
        }

        $this->validate($reglas);

        // Aquí iría la lógica para guardar los datos del formulario

        session()->flash('message', 'Formulario guardado correctamente.');
    }
}
