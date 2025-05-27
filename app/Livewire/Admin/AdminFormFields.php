<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Zona;
use App\Models\FormField;
use Livewire\WithPagination;

class AdminFormFields extends Component
{
    use WithPagination;
    
    public $zonaId;
    public $zona;
    public $showModal = false;
    public $editMode = false;
    public $formField = [];
    
    protected $rules = [
        'formField.campo' => 'required|string|max:255',
        'formField.etiqueta' => 'required|string|max:255',
        'formField.tipo' => 'required|in:text,email,tel,number,select,radio,checkbox',
        'formField.obligatorio' => 'boolean',
        'formField.orden' => 'integer|min:0'
    ];
    
    protected $listeners = ['fieldUpdated' => '$refresh'];
    
    public function mount($zonaId)
    {
        $this->zonaId = $zonaId;
        $this->zona = Zona::findOrFail($zonaId);
        $this->resetFormField();
    }
    
    public function resetFormField()
    {
        $this->formField = [
            'id' => null,
            'zona_id' => $this->zonaId,
            'campo' => '',
            'etiqueta' => '',
            'tipo' => 'text',
            'obligatorio' => true,
            'orden' => 0
        ];
        
        // Establecer el orden al último + 1
        $ultimoCampo = FormField::where('zona_id', $this->zonaId)
            ->orderBy('orden', 'desc')
            ->first();
            
        if ($ultimoCampo) {
            $this->formField['orden'] = $ultimoCampo->orden + 1;
        }
    }
    
    public function crear()
    {
        $this->resetFormField();
        $this->editMode = false;
        $this->showModal = true;
    }
    
    public function editar(FormField $campo)
    {
        $this->formField = $campo->toArray();
        $this->editMode = true;
        $this->showModal = true;
    }
    
    public function guardar()
    {
        $this->validate();
        
        if ($this->editMode) {
            $campo = FormField::find($this->formField['id']);
            $campo->update($this->formField);
            $mensaje = '¡Campo actualizado correctamente!';
        } else {
            FormField::create($this->formField);
            $mensaje = '¡Campo creado correctamente!';
        }
        
        $this->showModal = false;
        $this->resetFormField();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $mensaje
        ]);
    }
    
    public function eliminar($id)
    {
        $campo = FormField::find($id);
        
        if ($campo) {
            $campo->delete();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '¡Campo eliminado correctamente!'
            ]);
        }
    }
    
    public function administrarOpciones($id)
    {
        return redirect()->route('admin.form-fields.options', ['formField' => $id]);
    }
    
    public function render()
    {
        $campos = FormField::where('zona_id', $this->zonaId)
            ->orderBy('orden')
            ->paginate(10);
            
        return view('livewire.admin.admin-form-fields', [
            'campos' => $campos,
        ]);
    }
}
