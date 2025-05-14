<?php

namespace App\Livewire\Admin\Permissions;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

#[Layout('components.layouts.admin-dashboard')]
#[Title('Administración de Permisos')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Modal state
    public $showPermissionModal = false;
    public $permissionId = null;
    public $confirmingPermissionDeletion = false;

    // Form fields
    public $name = '';

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:permissions,name,' . $this->permissionId,
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openPermissionModal($permissionId = null)
    {
        $this->resetValidation();
        $this->permissionId = $permissionId;

        if ($permissionId) {
            $permission = Permission::findOrFail($permissionId);
            $this->name = $permission->name;
        } else {
            $this->name = '';
        }

        $this->showPermissionModal = true;
    }

    public function savePermission()
    {
        $this->validate();

        if ($this->permissionId) {
            $permission = Permission::findOrFail($this->permissionId);
            $permission->update([
                'name' => $this->name,
            ]);
        } else {
            Permission::create([
                'name' => $this->name,
            ]);
        }

        $this->showPermissionModal = false;
        $this->dispatch('permission-saved');
    }

    public function confirmPermissionDeletion($permissionId)
    {
        $this->confirmingPermissionDeletion = $permissionId;
    }

    public function deletePermission()
    {
        $permission = Permission::findOrFail($this->confirmingPermissionDeletion);
        // Verificar que no sea un permiso esencial
        $essentialPermissions = ['create users', 'edit users', 'delete users', 'view users'];
        if (in_array($permission->name, $essentialPermissions)) {
            session()->flash('error', 'No se puede eliminar un permiso esencial del sistema.');
        } else {
            $permission->delete();
            $this->dispatch('permission-deleted');
        }

        $this->confirmingPermissionDeletion = false;
    }

    public function render()
    {
        $permissions = Permission::where('name', 'like', '%' . $this->search . '%')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.permissions.index', [
            'permissions' => $permissions,
        ]);
    }
}
