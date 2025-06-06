<div>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Gestión de Roles</h1>
                <button
                    wire:click="openRoleModal"
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

    public function rules() {
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $this->roleId,
            'selectedPermissions' => 'array',
        ];
    }

    public function mount() {
        // Verificar que el usuario tenga el rol admin
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }
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

    public function openCreateModal() {
        $this->reset(['roleId', 'name', 'selectedPermissions']);
        $this->showModal = true;
    }

    public function openEditModal($roleId) {
        $this->roleId = $roleId;

        $role = Role::with('permissions')->findOrFail($roleId);
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();

        $this->showModal = true;
    }

    public function save() {
        $this->validate();

        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);
            $role->update([
                'name' => $this->name,
            ]);
        } else {
            $role = Role::create([
                'name' => $this->name,
            ]);
        }

        $role->syncPermissions($this->selectedPermissions);

        $this->reset(['showModal', 'roleId', 'name', 'selectedPermissions']);
        $this->dispatch('role-saved');
    }

    public function confirmDelete($roleId) {
        $this->confirmingRoleDeletion = $roleId;
    }

    public function deleteRole() {
        $role = Role::findOrFail($this->confirmingRoleDeletion);

        // Verificar que no sea un rol esencial del sistema
        if (in_array($role->name, ['admin', 'super-admin'])) {
            session()->flash('error', 'No se puede eliminar un rol esencial del sistema.');
        } else {
            $role->delete();
            $this->dispatch('role-deleted');
        }

        $this->confirmingRoleDeletion = false;
    }

    public function cancelDelete() {
        $this->confirmingRoleDeletion = false;
    }

    public function getRolesProperty() {
        return Role::where('name', 'like', "%{$this->search}%")
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function getPermissionsProperty() {
        return Permission::orderBy('name')->get();
    }

    // Agrupar permisos por categoría (basado en prefijos como "crear", "editar", etc)
    public function getGroupedPermissionsProperty() {
        $permissions = $this->permissions;
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode(' ', $permission->name);
            $action = $parts[0] ?? 'otros';

            if (!isset($grouped[$action])) {
                $grouped[$action] = [];
            }

            $grouped[$action][] = $permission;
        }

        return $grouped;
    }
}
?>

<div>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-base font-semibold leading-6 text-gray-900">Roles</h1>
                <p class="mt-2 text-sm text-gray-700">Listado de roles del sistema</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button type="button"
                        x-on:click="$wire.openCreateModal()"
                        class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Agregar Rol
                </button>
            </div>
        </div>

        <div class="mt-8 flow-root">
            <!-- Filtros -->
            <div class="mb-4">
                <div class="relative mt-2 rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input x-model="$wire.search" x-on:input.debounce.300ms="$wire.$refresh()" type="text" name="search" id="search" class="w-full rounded-md border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="Buscar roles...">
                </div>
            </div>

            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                        <div class="flex items-center">
                                            <button x-on:click="$wire.sortBy('name')" class="group inline-flex">
                                                Nombre del Rol
                                                <span class="ml-2 flex-none rounded text-gray-400">
                                                    @if($sortField === 'name')
                                                        @if($sortDirection === 'asc')
                                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd" />
                                                            </svg>
                                                        @else
                                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                            </svg>
                                                        @endif
                                                    @endif
                                                </span>
                                            </button>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Permisos</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($this->roles as $role)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                            {{ $role->name }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($role->permissions as $permission)
                                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                        {{ $permission->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <button x-on:click="$wire.openEditModal({{ $role->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                Editar
                                            </button>
                                            @if(!in_array($role->name, ['admin', 'super-admin']))
                                                <button x-on:click="$wire.confirmDelete({{ $role->id }})" class="text-red-600 hover:text-red-900">
                                                    Eliminar
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 text-center">
                                            No se encontraron roles
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                {{ $this->roles->links() }}
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar rol -->
    <x-dialog-modal x-bind:open="$wire.showModal" x-on:close="$wire.$set('showModal', false)">
        <x-slot name="title">
            {{ $roleId ? 'Editar Rol' : 'Crear Rol' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="name" value="Nombre del Rol" />
                    <x-input id="name" wire:model="name" class="block mt-1 w-full" type="text" />
                    <x-input-error for="name" class="mt-1" />
                </div>

                <div>
                    <x-label value="Permisos" />
                    <div class="mt-4">
                        <div class="border rounded-md p-4">
                            @foreach($this->groupedPermissions as $action => $permissionGroup)
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2 capitalize border-b pb-2">{{ $action }}</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                        @foreach($permissionGroup as $permission)
                                            <div class="flex items-center">
                                                <input
                                                    id="permission-{{ $permission->id }}"
                                                    x-model="$wire.selectedPermissions"
                                                    type="checkbox"
                                                    value="{{ $permission->id }}"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                >
                                                <label for="permission-{{ $permission->id }}" class="ml-3 block text-sm leading-6 text-gray-900">
                                                    {{ $permission->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <x-input-error for="selectedPermissions" class="mt-1" />
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end space-x-2">
                <x-secondary-button x-on:click="$wire.$set('showModal', false)">
                    Cancelar
                </x-secondary-button>

                <x-button x-on:click="$wire.save()">
                    {{ $roleId ? 'Actualizar' : 'Crear' }}
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    <!-- Confirmación de eliminación -->
    <x-confirmation-modal x-bind:open="$wire.confirmingRoleDeletion" x-on:close="$wire.$set('confirmingRoleDeletion', false)">
        <x-slot name="title">
            Eliminar Rol
        </x-slot>

        <x-slot name="content">
            ¿Estás seguro de que deseas eliminar este rol? Esta acción eliminará el rol pero no afectará a los usuarios que lo tienen asignado.
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end space-x-2">
                <x-secondary-button x-on:click="$wire.cancelDelete()">
                    Cancelar
                </x-secondary-button>

                <x-danger-button x-on:click="$wire.deleteRole()">
                    Eliminar
                </x-danger-button>
            </div>
        </x-slot>
    </x-confirmation-modal>
</div>
