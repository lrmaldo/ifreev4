<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.admin-dashboard')]
#[Title('Administración de Usuarios')]
class Index extends Component
{
    use WithPagination;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }
    }

    // Variables para el buscador
    public $search = '';
    public $perPage = 10;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    // Variables para el formulario de edición/creación
    public $userId;
    public $name = '';
    public $email = '';
    public $password = '';
    public $cliente_id = null;
    public $selectedRoles = [];

    // Estado del modal
    public $showModal = false;
    public $isEditing = false;
    public $confirmingUserDeletion = false;

    // Validación de campos (reglas aplicadas a las propiedades ya definidas)
    public function rules()
    {
        $passwordRule = $this->userId ? 'nullable|min:8' : 'required|min:8';

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->userId,
            'password' => $passwordRule,
            'cliente_id' => 'nullable|exists:clientes,id',
            'selectedRoles' => 'array',
        ];
    }

    // Reset paginación cuando cambia la búsqueda
    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orderBy($this->sortField, $this->sortDirection)
                    ->paginate($this->perPage);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    public function sortBy($field)
    {
        if($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openModal($userId = null)
    {
        $this->resetValidation();
        $this->reset(['name', 'email', 'password', 'cliente_id', 'selectedRoles']);

        $this->isEditing = !is_null($userId);

        if($this->isEditing) {
            $user = User::findOrFail($userId);
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->cliente_id = $user->cliente_id;
            $this->selectedRoles = $user->roles()->pluck('id')->toArray();
        }

        $this->showModal = true;
    }

    public function save()
    {
        $validatedData = $this->validate();

        if($this->isEditing || $this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
                'cliente_id' => $this->cliente_id,
            ]);

            if(!empty($this->password)) {
                $user->update([
                    'password' => Hash::make($this->password),
                ]);
            }
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'cliente_id' => $this->cliente_id,
                'password' => Hash::make($this->password),
            ]);
        }

        // Sincronizar roles
        if (!empty($this->selectedRoles)) {
            // Asegurarse de que estamos pasando los roles como IDs y no como nombres
            $roles = Role::whereIn('id', $this->selectedRoles)->get();
            $user->syncRoles($roles);
        } else {
            $user->syncRoles([]);
        }

        $this->showModal = false;
        $this->dispatch('user-saved');
    }

    public function confirmUserDeletion($userId)
    {
        $this->confirmingUserDeletion = $userId;
    }

    public function deleteUser()
    {
        $user = User::findOrFail($this->confirmingUserDeletion);
        $user->delete();
        $this->confirmingUserDeletion = false;
        $this->dispatch('user-deleted');
    }

    public function render()
    {
        return view('livewire.admin.users.index');
    }
}
