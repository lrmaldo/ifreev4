<?php

use App\Models\User;
use App\Models\Cliente;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;
    public $loading = false;

    // Modal state
    public $showModal = false;
    public $userId = null;
    public $confirmingUserDeletion = false;

    // Initialize Alpine.js data
    public function mount() {
        $this->showModal = false;
        $this->confirmingUserDeletion = false;
        $this->authorize('view users');
    }

    // Form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $cliente_id = null;
    public $selectedRoles = [];

    // Abrir el modal para crear o editar usuario
    public function openModal($userId = null) {
        $this->userId = $userId;

        if ($userId) {
            $user = User::findOrFail($userId);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->password = '';
            $this->cliente_id = $user->cliente_id;
            $this->selectedRoles = $user->roles->pluck('id')->toArray();
        } else {
            $this->reset(['name', 'email', 'password', 'cliente_id', 'selectedRoles']);
        }

        $this->showModal = true;
    }

    public function rules() {
        $passwordRule = $this->userId ? 'nullable|min:8' : 'required|min:8';

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->userId,
            'password' => $passwordRule,
            'cliente_id' => 'nullable|exists:clientes,id',
            'selectedRoles' => 'array',
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



    public function save() {
        try {
            $this->loading = true;
            $this->authorize($this->userId ? 'edit users' : 'create users');

            $validatedData = $this->validate();

            if ($this->userId) {
                $user = User::findOrFail($this->userId);
                $user->name = $this->name;
                $user->email = $this->email;
                $user->cliente_id = $this->cliente_id;

                if ($this->password) {
                    $user->password = Hash::make($this->password);
                }

                $user->save();
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                    'cliente_id' => $this->cliente_id,
                ]);
            }

            // Sincronizar los roles
            $user->syncRoles($this->selectedRoles);

            $this->reset(['showModal', 'userId', 'name', 'email', 'password', 'cliente_id', 'selectedRoles']);

            $this->dispatch('user-saved');
        } finally {
            $this->loading = false;
        }
    }

    public function confirmDelete($userId) {
        $this->confirmingUserDeletion = $userId;
    }

    public function deleteUser() {
        $this->authorize('delete users');

        $user = User::findOrFail($this->confirmingUserDeletion);
        $user->delete();

        $this->confirmingUserDeletion = false;
        $this->dispatch('user-deleted');
    }

    public function cancelDelete() {
        $this->confirmingUserDeletion = false;
    }

    public function getUsersProperty() {
        return User::where('name', 'like', "%{$this->search}%")
            ->orWhere('email', 'like', "%{$this->search}%")
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function getRolesProperty() {
        return Role::orderBy('name')->get();
    }

    public function getClientesProperty() {
        return Cliente::orderBy('nombre_comercial')->get();
    }
}
?>

<div>
    <div class="px-4 sm:px-6 lg:px-8 bg-white rounded-lg shadow-sm">
        <div class="sm:flex sm:items-center py-5 border-b border-gray-200">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold leading-6 text-gray-800">Usuarios</h1>
                <p class="mt-2 text-sm text-gray-500">Administración de usuarios del sistema</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button type="button"
                        wire:click="openModal()"
                        class="flex items-center rounded-md bg-blue-50 px-4 py-2 text-center text-sm font-medium text-blue-700 hover:bg-blue-100 transition-colors duration-150 ease-in-out shadow-sm border border-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Agregar Usuario
                </button>
            </div>
        </div>

        <div class="mt-6 flow-root">
            <!-- Filtros -->
            <div class="mb-4">
                <div class="relative mt-2 rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input x-model="$wire.search" x-on:input.debounce.300ms="$wire.$refresh()" type="text" name="search" id="search" class="w-full rounded-md border border-gray-300 py-2 pl-10 text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Buscar usuarios por nombre o email...">
                </div>
            </div>

            <div class="overflow-x-auto">
                <div class="inline-block min-w-full py-2 align-middle">
                    <div class="overflow-hidden rounded-lg shadow">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-medium text-gray-700 sm:pl-6">
                                        <div class="group inline-flex items-center select-none">
                                            <button wire:click="sortBy('name')" class="group inline-flex items-center">
                                                Nombre
                                                <span class="ml-2 flex-none text-gray-400">
                                                    @if($sortField === 'name')
                                                        @if($sortDirection === 'asc')
                                                            <svg class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd" />
                                                            </svg>
                                                        @else
                                                            <svg class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                            </svg>
                                                        @endif
                                                    @endif
                                                </span>
                                            </button>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-medium text-gray-700">
                                        <div class="flex items-center">
                                            <button wire:click="sortBy('email')" class="group inline-flex items-center">
                                                Email
                                                <span class="ml-2 flex-none text-gray-400">
                                                    @if($sortField === 'email')
                                                        @if($sortDirection === 'asc')
                                                            <svg class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd" />
                                                            </svg>
                                                        @else
                                                            <svg class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                            </svg>
                                                        @endif
                                                    @endif
                                                </span>
                                            </button>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-medium text-gray-700">Roles</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-medium text-gray-700">Permisos</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right text-sm font-medium text-gray-700">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($this->users as $user)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                            {{ $user->name }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">
                                            {{ $user->email }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-600">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($user->roles as $role)
                                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-700/10">
                                                        {{ $role->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-600">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($user->getAllPermissions() as $permission)
                                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-700/10">
                                                        {{ $permission->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <button wire:click="openModal({{ $user->id }})" class="inline-flex items-center px-3 py-1 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-md border border-amber-200 mr-2 transition-colors duration-150">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Editar
                                            </button>
                                            <button wire:click="confirmUserDeletion({{ $user->id }})" class="inline-flex items-center px-3 py-1 bg-red-50 text-red-700 hover:bg-red-100 rounded-md border border-red-200 transition-colors duration-150">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-sm text-gray-500 bg-gray-50">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p>No se encontraron usuarios</p>
                                                <button wire:click="openModal()" class="mt-3 inline-flex items-center px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md border border-blue-200 transition-colors duration-150">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Crear primer usuario
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>            <div class="mt-6 pb-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        @if($this->users->hasPages())
                            <button x-on:click="$wire.$dispatch('changePage', {page: {{ $this->users->currentPage() - 1 }}})" @if(!$this->users->onFirstPage()) disabled @endif
                                class="{{ !$this->users->onFirstPage() ? 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' }} relative inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md">
                                Anterior
                            </button>
                            <button x-on:click="$wire.$dispatch('changePage', {page: {{ $this->users->currentPage() + 1 }}})" @if(!$this->users->hasMorePages()) disabled @endif
                                class="{{ $this->users->hasMorePages() ? 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' }} relative inline-flex items-center px-4 py-2 ml-3 border text-sm font-medium rounded-md">
                                Siguiente
                            </button>
                        @endif
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Mostrando
                                <span class="font-medium">{{ $this->users->firstItem() ?? 0 }}</span>
                                al
                                <span class="font-medium">{{ $this->users->lastItem() ?? 0 }}</span>
                                de
                                <span class="font-medium">{{ $this->users->total() }}</span>
                                resultados
                            </p>
                        </div>
                        <div>
                            {{ $this->users->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar usuario -->
    <x-dialog-modal wire:model="showModal">
        <x-slot name="title">
            <div class="flex items-center">
                @if($userId)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Editar Usuario</span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>Crear Nuevo Usuario</span>
                @endif
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-5 py-2">
                <div>
                    <x-label for="name" value="Nombre Completo" />
                    <x-input id="name" wire:model="name" class="border-gray-300 focus:border-primary-600 focus:ring-primary-600 rounded-md shadow-sm w-full" type="text" autocomplete="name" placeholder="Nombre completo del usuario" />
                    <x-input-error for="name" class="mt-1" />
                </div>

                <div>
                    <x-label for="email" value="Correo Electrónico" />
                    <x-input id="email" wire:model="email" class="border-gray-300 focus:border-primary-600 focus:ring-primary-600 rounded-md shadow-sm w-full" type="email" autocomplete="email" placeholder="correo@ejemplo.com" />
                    <x-input-error for="email" class="mt-1" />
                </div>

                <div>
                    <x-label for="password" value="{{ $userId ? 'Contraseña (dejar en blanco para mantener la actual)' : 'Contraseña' }}" />
                    <x-input id="password" wire:model="password" class="border-gray-300 focus:border-primary-600 focus:ring-primary-600 rounded-md shadow-sm w-full" type="password" autocomplete="new-password" />
                    <x-input-error for="password" class="mt-1" />
                </div>

                <div>
                    <x-label for="cliente_id" value="Cliente Asociado" />
                    <select id="cliente_id" wire:model="cliente_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">-- Seleccionar cliente --</option>
                        @foreach($this->clientes ?? [] as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nombre_comercial ?? $cliente->razon_social ?? 'Cliente '.$cliente->id }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="cliente_id" class="mt-1" />
                </div>

                <div>
                    <x-label value="Roles del Usuario" class="mb-2" />
                    <div class="mt-2 space-y-2 border rounded-md p-3 bg-gray-50 max-h-48 overflow-y-auto">
                        @foreach($this->roles as $role)
                            <div class="flex items-center">                                                <input
                                                    id="role-{{ $role->id }}"
                                                    wire:model="selectedRoles"
                                                    type="checkbox"
                                                    value="{{ $role->id }}"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
                                                >
                                <label for="role-{{ $role->id }}" class="ml-3 block text-sm leading-6 text-gray-700 select-none">
                                    {{ $role->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <x-input-error for="selectedRoles" class="mt-1" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end space-x-3">                <x-secondary-button wire:click="$set('showModal', false)" class="px-4">
                    Cancelar
                </x-secondary-button>

                <x-button wire:click="save" wire:loading.attr="disabled" class="px-4">
                    <span wire:loading.remove wire:target="save">
                        {{ $userId ? 'Guardar Cambios' : 'Crear Usuario' }}
                    </span>
                    <span wire:loading wire:target="save">
                        Procesando...
                    </span>
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>    <!-- Confirmación de eliminación -->
    <x-confirmation-modal wire:model="confirmingUserDeletion">
        <x-slot name="title">
            <div class="flex items-center text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Confirmar eliminación
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="py-4">
                <div class="flex items-center justify-center mb-4 text-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <p class="text-center text-gray-700">¿Estás seguro de que deseas eliminar a este usuario?</p>
                <p class="text-center text-gray-500 text-sm mt-2">Esta acción no se puede deshacer.</p>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-center space-x-4">                <x-secondary-button wire:click="cancelDelete" class="px-4 py-2">
                    Cancelar
                </x-secondary-button>

                <x-danger-button wire:click="deleteUser" class="px-4 py-2">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Confirmar Eliminación
                    </div>
                </x-danger-button>
            </div>
        </x-slot>
    </x-confirmation-modal>

    <!-- Mensaje de notificación -->
    <div x-data="{ show: false, message: '' }"
         @user-saved.window="show = true; message = 'Usuario guardado correctamente'; setTimeout(() => show = false, 3000)"
         @user-deleted.window="show = true; message = 'Usuario eliminado correctamente'; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed bottom-4 right-4 bg-green-50 border border-green-200 text-green-700 px-6 py-3 rounded-lg shadow-lg flex items-center z-50"
         style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span x-text="message"></span>
    </div>
</div>
