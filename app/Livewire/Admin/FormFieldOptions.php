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

    public const ESTADOS_MEXICO = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua',
        'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México', 'Guanajuato', 'Guerrero',
        'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro',
        'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz',
        'Yucatán', 'Zacatecas', 'Fuera de México',
    ];

    /** Agrega los 32 estados (y "Fuera de México") sin repetir los que ya estén. */
    public function cargarEstados()
    {
        $existentes = $this->formField->opciones()->pluck('valor')->all();
        $orden = (int) $this->formField->opciones()->max('orden');
        $agregados = 0;

        foreach (self::ESTADOS_MEXICO as $estado) {
            if (! in_array($estado, $existentes, true)) {
                FormFieldOption::create([
                    'form_field_id' => $this->formField->id,
                    'valor' => $estado,
                    'etiqueta' => $estado,
                    'orden' => ++$orden,
                ]);
                $agregados++;
            }
        }

        $this->formField->load('opciones');
        $this->cargarOpciones();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $agregados ? "Se agregaron {$agregados} estados" : 'Los estados ya estaban cargados',
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
