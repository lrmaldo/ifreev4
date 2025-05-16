<div>
    <script>
        document.addEventListener('livewire:initialized', function () {
            // Escucha los cambios en la propiedad showInstructionsModal
            Livewire.on('showInstructionsModal', function() {
                console.log('Modal debe mostrarse ahora!');
                let modal = document.getElementById('instructions-modal');
                if (modal) {
                    modal.style.display = 'block';
                }
            });
        });
    </script>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:leading-9 sm:truncate">
                    Gestión de Zonas
                </h2>
            </div>
            <div class="mt-4 flex sm:mt-0 sm:ml-4">
                <button
                    wire:click="openModal()"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Nueva Zona
                </button>
            </div>
        </div>

        <!-- Mensajes flash -->
        @if (session()->has('message'))
            <div class="mb-4 p-4 bg-green-100 text-green-900 rounded-md shadow-sm">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-900 rounded-md shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Controles de búsqueda y paginación -->
        <div class="mb-6 sm:flex sm:items-center sm:justify-between">
            <div class="w-full max-w-sm">
                <label for="search" class="sr-only">Buscar</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        wire:model.live.debounce.300ms="search"
                        id="search"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Buscar por nombre..."
                        type="search"
                    >
                </div>
            </div>
            <div class="mt-3 sm:mt-0">
                <select
                    wire:model.live="perPage"
                    id="perPage"
                    class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                    <option value="5">5 por página</option>
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>
        </div>

        <!-- Tabla de zonas -->
        <div class="overflow-x-auto bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nombre
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo de Registro
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cuenta regresiva (Segundos)
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Auth Mikrotik
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Propietario
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Campos
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($zonas as $zona)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $zona->nombre }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $zona->getTipoRegistroLabelAttribute() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $zona->segundos }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $zona->getTipoAutenticacionMikrotikLabelAttribute() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $zona->user->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($zona->tipo_registro != 'sin_registro')
                                    {{ $zona->campos->count() }}
                                    <button
                                        wire:click="openFieldModal({{ $zona->id }})"
                                        class="ml-2 inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </button>
                                @else
                                    <span class="italic text-gray-400">No aplica</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button
                                        wire:click="openModal(true, {{ $zona->id }})"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Editar
                                    </button>
                                    
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" class="text-blue-600 hover:text-blue-900 focus:outline-none">
                                            Archivos Mikrotik
                                            <svg class="h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                            <div class="py-1">
                                                <a href="{{ route('admin.zonas.download', ['zonaId' => $zona->id, 'fileType' => 'login']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Descargar login.html
                                                </a>
                                                <a href="{{ route('admin.zonas.download', ['zonaId' => $zona->id, 'fileType' => 'alogin']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Descargar alogin.html
                                                </a>
                                                <button 
                                                    wire:click.prevent="openInstructionsModal({{ $zona->id }})"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    Ver instrucciones
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if (auth()->user()->hasRole('admin') || $zona->user_id === auth()->id())
                                        <button
                                            wire:click="confirmZonaDeletion({{ $zona->id }})"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Eliminar
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <!-- Lista de campos por zona -->
                        @if ($zona->campos->count() > 0 && $zona->tipo_registro != 'sin_registro')
                            <tr class="bg-gray-50">
                                <td colspan="7" class="px-6 py-2">
                                    <div class="border rounded-md divide-y">
                                        <div class="px-4 py-2 bg-gray-100 text-sm font-medium">
                                            Campos de "{{ $zona->nombre }}"
                                        </div>
                                        <div class="divide-y">
                                            @foreach ($zona->campos->sortBy('orden') as $campo)
                                                <div class="px-4 py-2 flex justify-between items-center">
                                                    <div class="flex-1">
                                                        <div class="flex items-center">
                                                            <span class="font-medium">{{ $campo->etiqueta }}</span>
                                                            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100">
                                                                {{ $campo->tipo }}
                                                            </span>
                                                            @if ($campo->obligatorio)
                                                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                                    Obligatorio
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            Campo: {{ $campo->campo }} | Orden: {{ $campo->orden }}
                                                        </div>
                                                    </div>
                                                    <div class="flex space-x-2">
                                                        <button
                                                            wire:click="openFieldModal({{ $zona->id }}, true, {{ $campo->id }})"
                                                            class="text-indigo-600 hover:text-indigo-900 text-sm"
                                                        >
                                                            Editar
                                                        </button>
                                                        <button
                                                            wire:click="confirmFieldDeletion({{ $campo->id }})"
                                                            class="text-red-600 hover:text-red-900 text-sm"
                                                        >
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                No hay zonas disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4">
            {{ $zonas->links() }}
        </div>

        <!-- Modal para crear/editar zona -->
        @if ($showModal)
            <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        {{ $isEditing ? 'Editar Zona' : 'Nueva Zona' }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                                            <input
                                                type="text"
                                                wire:model="zona.nombre"
                                                id="nombre"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('zona.nombre')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="tipo_registro" class="block text-sm font-medium text-gray-700">Tipo de Registro</label>
                                            <select
                                                wire:model="zona.tipo_registro"
                                                id="tipo_registro"
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            >
                                                @foreach ($tipoRegistroOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('zona.tipo_registro')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="segundos" class="block text-sm font-medium text-gray-700">Segundos (Tiempo retroceso)</label>
                                            <input
                                                type="number"
                                                wire:model="zona.segundos"
                                                id="segundos"
                                                min="15"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('zona.segundos')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                type="checkbox"
                                                wire:model="zona.login_sin_registro"
                                                id="login_sin_registro"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            >
                                            <label for="login_sin_registro" class="ml-2 block text-sm text-gray-900">
                                                Boton "no quiero registrarme"
                                            </label>
                                        </div>
                                        <div>
                                            <label for="tipo_autenticacion_mikrotik" class="block text-sm font-medium text-gray-700">Tipo de Autenticación Mikrotik</label>
                                            <select
                                                wire:model="zona.tipo_autenticacion_mikrotik"
                                                id="tipo_autenticacion_mikrotik"
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            >
                                                @foreach ($tipoAutenticacionMikrotikOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('zona.tipo_autenticacion_mikrotik')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="script_head" class="block text-sm font-medium text-gray-700">Script Head</label>
                                            <textarea
                                                wire:model="zona.script_head"
                                                id="script_head"
                                                rows="3"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            ></textarea>
                                        </div>
                                        <div>
                                            <label for="script_body" class="block text-sm font-medium text-gray-700">Script Body</label>
                                            <textarea
                                                wire:model="zona.script_body"
                                                id="script_body"
                                                rows="3"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="saveZona"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Guardar
                            </button>
                            <button
                                wire:click="closeModal"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal para crear/editar campo -->
        @if ($showFieldModal)
            <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        {{ $isEditingField ? 'Editar Campo' : 'Nuevo Campo' }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="campo" class="block text-sm font-medium text-gray-700">Identificador del Campo</label>
                                            <input
                                                type="text"
                                                wire:model="formField.campo"
                                                id="campo"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('formField.campo')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="etiqueta" class="block text-sm font-medium text-gray-700">Etiqueta</label>
                                            <input
                                                type="text"
                                                wire:model="formField.etiqueta"
                                                id="etiqueta"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('formField.etiqueta')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo de Campo</label>
                                            <select
                                                wire:model="formField.tipo"
                                                id="tipo"
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            >
                                                @foreach ($tipoFieldOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('formField.tipo')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="orden" class="block text-sm font-medium text-gray-700">Orden</label>
                                            <input
                                                type="number"
                                                wire:model="formField.orden"
                                                id="orden"
                                                min="0"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('formField.orden')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                type="checkbox"
                                                wire:model="formField.obligatorio"
                                                id="obligatorio"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            >
                                            <label for="obligatorio" class="ml-2 block text-sm text-gray-900">
                                                Campo obligatorio
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="saveField"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Guardar
                            </button>
                            <button
                                wire:click="closeFieldModal"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de confirmación para eliminar zona -->
        @if ($confirmingZonaDeletion)
            <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Eliminar Zona
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            ¿Estás seguro de que deseas eliminar esta zona? Esta acción eliminará también todos los campos asociados y no se puede deshacer.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="deleteZona"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Eliminar
                            </button>
                            <button
                                wire:click="$set('confirmingZonaDeletion', false)"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de confirmación para eliminar campo -->
        @if ($confirmingFieldDeletion)
            <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Eliminar Campo
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            ¿Estás seguro de que deseas eliminar este campo del formulario? Esta acción no se puede deshacer.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="deleteField"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Eliminar
                            </button>
                            <button
                                wire:click="$set('confirmingFieldDeletion', false)"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de instrucciones de instalación (Versión simplificada) -->
        @if ($showInstructionsModal)
            <div id="instructions-modal" class="fixed z-50 inset-0 overflow-y-auto" style="display: block !important;">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                    
                    <!-- Modal content -->
                    <div class="relative bg-white rounded-lg w-full max-w-2xl mx-auto p-6" style="z-index: 100;">
                        <h3 class="text-xl font-bold mb-4">Instrucciones de Instalación</h3>
                        
                        @if($activeZonaForInstructions)
                            <h4 class="font-semibold text-lg mb-3">Zona: {{ $activeZonaForInstructions->nombre }}</h4>
                            
                            <!-- Pasos sencillos -->
                            <div class="mb-4 p-3 bg-gray-50 rounded">
                                <h5 class="font-bold">1. Descargar archivos</h5>
                                <p>Descarga los archivos login.html y alogin.html desde el menú de acciones.</p>
                            </div>
                            
                            <div class="mb-4 p-3 bg-gray-50 rounded">
                                <h5 class="font-bold">2. Subir a Mikrotik</h5>
                                <p>Sube los archivos a tu router Mikrotik en la carpeta Hotspot.</p>
                            </div>
                            
                            <div class="mb-4 p-3 bg-gray-50 rounded">
                                <h5 class="font-bold">3. Configurar autenticación</h5>
                                <p>Tipo de autenticación configurado: <strong>{{ $activeZonaForInstructions->tipo_autenticacion_mikrotik_label }}</strong></p>
                            </div>
                        @else
                            <p class="text-red-500">No se ha seleccionado ninguna zona.</p>
                        @endif
                        
                        <!-- Botón de cerrar -->
                        <div class="mt-6 flex justify-end">
                            <button 
                                wire:click="closeInstructionsModal"
                                type="button"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Instrucciones de Instalación - {{ $activeZonaForInstructions->nombre }}
                                    </h3>
                                    <div class="mt-2">
                                        <h4 class="font-bold text-base mb-2">Configuración en Mikrotik RouterOS</h4>
                                        
                                        <div class="mb-4 p-3 bg-gray-50 rounded-md">
                                            <h5 class="font-bold mb-2">1. Configuración de Hotspot</h5>
                                            <p class="mb-2">Para configurar correctamente el portal cautivo en Mikrotik con los archivos proporcionados:</p>
                                            <ol class="list-decimal pl-5 space-y-2">
                                                <li>Accede a tu router Mikrotik mediante WinBox o SSH.</li>
                                                <li>Ve a IP > Hotspot y configura un nuevo servidor hotspot.</li>
                                                <li>Después de la configuración básica, accede a la carpeta de archivos HTML.</li>
                                                <li>Reemplaza los archivos login.html y alogin.html con los que has descargado.</li>
                                            </ol>
                                        </div>
                                        
                                        <div class="mb-4 p-3 bg-gray-50 rounded-md">
                                            <h5 class="font-bold mb-2">2. Tipo de Autenticación: {{ $activeZonaForInstructions->tipo_autenticacion_mikrotik_label }}</h5>
                                            <p class="mb-2">Esta zona está configurada para autenticación por {{ $activeZonaForInstructions->tipo_autenticacion_mikrotik_label }}.</p>
                                            
                                            @if($activeZonaForInstructions->tipo_autenticacion_mikrotik == 'pin')
                                                <p>Para la autenticación por PIN:</p>
                                                <ul class="list-disc pl-5 space-y-1">
                                                    <li>Los usuarios solo necesitan ingresar un código PIN para autenticarse.</li>
                                                    <li>Debes crear usuarios en Mikrotik donde el nombre de usuario sea el PIN.</li>
                                                    <li>La contraseña puede ser la misma que el PIN o dejarla vacía.</li>
                                                </ul>
                                            @else
                                                <p>Para la autenticación por Usuario y Contraseña:</p>
                                                <ul class="list-disc pl-5 space-y-1">
                                                    <li>Los usuarios deberán ingresar tanto el nombre de usuario como la contraseña.</li>
                                                    <li>Debes crear usuarios en Mikrotik con sus respectivos nombres y contraseñas.</li>
                                                </ul>
                                            @endif
                                        </div>
                                        
                                        <div class="p-3 bg-gray-50 rounded-md">
                                            <h5 class="font-bold mb-2">3. Ajustes adicionales</h5>
                                            <ul class="list-disc pl-5 space-y-2">
                                                <li>En Hotspot > Server Profiles, asegúrate de configurar la URL de redirección a tu portal: <span class="font-mono bg-gray-200 px-1 rounded">https://tu-portal-web.com/redirect?zona={{ $activeZonaForInstructions->id }}</span></li>
                                                <li>Si necesitas personalizar los archivos HTML descargados, puedes editar los estilos o agregar tu logo.</li>
                                                <li>Recuerda ajustar las reglas de firewall si es necesario para permitir el tráfico adecuado.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="closeInstructionsModal"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
