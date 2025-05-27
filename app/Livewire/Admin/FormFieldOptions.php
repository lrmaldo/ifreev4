<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\FormField;
use App\Models\FormFieldOption;

class FormFieldOptions extends Component
{
    public $formField;
    public $opciones = [];
    public $nuevaOpcion = [
        'valor' => '',
        'etiqueta' => '',
        'orden' => 0
    ];

    protected $rules = [
        'opciones.*.valor' => 'required',
        'opciones.*.etiqueta' => 'required',
        'opciones.*.orden' => 'integer',
        'nuevaOpcion.valor' => 'required',
        'nuevaOpcion.etiqueta' => 'required',
        'nuevaOpcion.orden' => 'integer'
    ];

    public function mount(FormField $formField)
    {
        $this->formField = $formField;
        $this->cargarOpciones();
    }

    public function cargarOpciones()
    {
        $this->opciones = $this->formField->opciones->toArray();

        if (empty($this->opciones)) {
            $this->nuevaOpcion['orden'] = 1;
        } else {
            $this->nuevaOpcion['orden'] = max(array_column($this->opciones, 'orden')) + 1;
        }
    }

    public function agregarOpcion()
    {
        $this->validate([
            'nuevaOpcion.valor' => 'required',
            'nuevaOpcion.etiqueta' => 'required',
        ]);

        FormFieldOption::create([
            'form_field_id' => $this->formField->id,
            'valor' => $this->nuevaOpcion['valor'],
            'etiqueta' => $this->nuevaOpcion['etiqueta'],
            'orden' => $this->nuevaOpcion['orden']
        ]);

        $this->nuevaOpcion = [
            'valor' => '',
            'etiqueta' => '',
            'orden' => $this->nuevaOpcion['orden'] + 1
        ];

        $this->cargarOpciones();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Opción agregada correctamente'
        ]);
    }

    public function actualizarOpcion($id)
    {
        $this->validate([
            "opciones.{$id}.valor" => 'required',
            "opciones.{$id}.etiqueta" => 'required',
        ]);

        $opcion = FormFieldOption::find($this->opciones[$id]['id']);

        if ($opcion) {
            $opcion->update([
                'valor' => $this->opciones[$id]['valor'],
                'etiqueta' => $this->opciones[$id]['etiqueta'],
                'orden' => $this->opciones[$id]['orden']
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Opción actualizada correctamente'
            ]);
        }
    }

    public function eliminarOpcion($id)
    {
        $opcion = FormFieldOption::find($this->opciones[$id]['id']);

        if ($opcion) {
            $opcion->delete();

            $this->cargarOpciones();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Opción eliminada correctamente'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.form-field-options');
    }
}
