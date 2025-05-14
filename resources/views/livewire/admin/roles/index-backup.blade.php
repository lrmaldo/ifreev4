@php
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Modal state
    public $showModal = false;
    public $roleId = null;
    public $confirmingRoleDeletion = false;

    // Form fields
    public $name = '';
    public $selectedPermissions = [];

    public function rules() {
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $this->roleId,
            'selectedPermissions' => 'array',
        ];
    }

    public function updatingSearch() {
        $this->resetPage();
    }

    public function sortBy($field) {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openModal($roleId = null) {
        $this->resetValidation();
        $this->reset(['name', 'selectedPermissions']);

        if ($roleId) {
            $role = Role::findOrFail($roleId);
            $this->roleId = $role->id;
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions()->pluck('id')->toArray();
        }

        $this->showModal = true;
    }

    public function save() {
        $this->validate();

        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);
            $role->update(['name' => $this->name]);
        } else {
            $role = Role::create(['name' => $this->name]);
        }

        // Sincronizar permisos
        $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();
        $role->syncPermissions($permissions);

        $this->showModal = false;
    }

    public function confirmRoleDeletion($roleId) {
        $this->confirmingRoleDeletion = $roleId;
    }

    public function deleteRole() {
        $role = Role::findOrFail($this->confirmingRoleDeletion);
        $role->delete();
        
        $this->confirmingRoleDeletion = false;
    }

    public function with(): array {
        return [
            'roles' => Role::where('name', 'like', '%' . $this->search . '%')
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->perPage),
            'permissions' => Permission::orderBy('name')->get(),
        ];
    }
}
@endphp

<div>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Gestión de Roles</h1>
                <button 
                    wire:click="openModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Nuevo Rol
                </button>
            </div>

            <!-- Buscador -->
            <div class="mb-4">
                <input 
                    wire:model.debounce.300ms="search"
                    type="search"
                    placeholder="Buscar..."
                    class="block w-full p-2 border border-gray-300 rounded-md">
            </div>

            <!-- Tabla de roles -->
            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th wire:click="sortBy('id')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">
                                ID
                                @if($sortField === 'id')
                                    @if($sortDirection === 'asc')
                                        <span>↑</span>
                                    @else
                                        <span>↓</span>
                                    @endif
                                @endif
                            </th>
                            <th wire:click="sortBy('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">
                                Nombre
                                @if($sortField === 'name')
                                    @if($sortDirection === 'asc')
                                        <span>↑</span>
                                    @else
                                        <span>↓</span>
                                    @endif
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Permisos
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($roles as $role)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $role->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $role->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-normal text-sm text-gray-500">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($role->permissions as $permission)
                                            <span class="bg-gray-100 text-gray-800 rounded-full px-2 py-1 text-xs">
                                                {{ $permission->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button 
                                        wire:click="openModal({{ $role->id }})" 
                                        class="text-indigo-600 hover:text-indigo-900">
                                        Editar
                                    </button>
                                    <button 
                                        wire:click="confirmRoleDeletion({{ $role->id }})" 
                                        class="ml-2 text-red-600 hover:text-red-900">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    No se encontraron roles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <div class="px-6 py-3">
                    {{ $roles->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar rol -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-10" 
         x-data="{ show: @entangle('showModal') }" 
         x-show="show"
         x-cloak>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form wire:submit="save" class="p-6">
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900">
                                {{ $roleId ? 'Editar Rol' : 'Nuevo Rol' }}
                            </h3>
                        </div>

                        <div class="mt-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input 
                                id="name"
                                type="text" 
                                wire:model="name"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" 
                                placeholder="Nombre del rol">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Permisos</label>
                            <div class="mt-2 h-64 overflow-y-auto p-2 border border-gray-300 rounded-md">
                                @foreach($permissions as $permission)
                                    <div class="flex items-start mb-2">
                                        <input 
                                            id="permission-{{ $permission->id }}"
                                            type="checkbox" 
                                            value="{{ $permission->id }}"
                                            wire:model="selectedPermissions"
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                        <label for="permission-{{ $permission->id }}" class="ml-2 block text-sm text-gray-900">
                                            {{ $permission->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('selectedPermissions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button 
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                                Guardar
                            </button>
                            <button 
                                type="button" 
                                wire:click="$set('showModal', false)"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-10" 
         x-data="{ show: @entangle('confirmingRoleDeletion') }" 
         x-show="show"
         x-cloak>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">¿Eliminar este rol?</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        ¿Estás seguro que deseas eliminar este rol? Esta acción no se puede deshacer.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button 
                            type="button"
                            wire:click="deleteRole"
                            class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                            Eliminar
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('confirmingRoleDeletion', false)"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
