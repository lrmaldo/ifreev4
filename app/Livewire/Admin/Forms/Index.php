<?php

namespace App\Livewire\Admin\Forms;

use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    public function render()
    {
        // Aquí irá la lógica para cargar los formularios
        // Por ahora solo renderizamos la vista
        return view('livewire.admin.forms.index');
    }
}
