<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Gestión de Chats de Telegram</h2>

        {{-- Mensajes Flash --}}
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Información del Bot --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex justify-between items-center mb-2">
            <h3 class="text-lg font-semibold text-blue-900">Información del Bot</h3>
            <button wire:click="getBotInfo" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm">
                Obtener Info
            </button>
        </div>
        @if($testMessage)
            <p class="text-blue-700">{{ $testMessage }}</p>
        @endif
    </div>

    {{-- Test de Conexión --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <h3 class="text-lg font-semibold text-yellow-900 mb-3">Probar Conexión</h3>
        <div class="flex gap-3">
            <input type="text"
                   wire:model="testChatId"
                   placeholder="Chat ID (ej: -1001234567890)"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md">
            <button wire:click="testConnection"
                    class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                Probar
            </button>
        </div>
        <p class="text-sm text-yellow-700 mt-2">
            💡 Para obtener el Chat ID: añade el bot al grupo y envía un mensaje. El Chat ID aparecerá en los logs.
        </p>
    </div>

    {{-- Formulario --}}
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">
            {{ $editingChatId ? 'Editar Chat' : 'Nuevo Chat' }}
        </h3>

        <form wire:submit="store">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chat ID</label>
                    <input type="text"
                           wire:model="chat_id"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md @error('chat_id') border-red-500 @enderror">
                    @error('chat_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                    <input type="text"
                           wire:model="nombre"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md @error('nombre') border-red-500 @enderror">
                    @error('nombre') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Chat</label>
                    <select wire:model="tipo"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md @error('tipo') border-red-500 @enderror">
                        <option value="private">💬 Privado</option>
                        <option value="group">👥 Grupo</option>
                        <option value="supergroup">🏢 Supergrupo</option>
                        <option value="channel">📢 Canal</option>
                    </select>
                    @error('tipo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="flex items-center space-x-2 mt-6">
                        <input type="checkbox"
                               wire:model="activo"
                               class="rounded border-gray-300">
                        <span class="text-sm font-medium text-gray-700">Activo</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea wire:model="descripcion"
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md @error('descripcion') border-red-500 @enderror"></textarea>
                @error('descripcion') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Zonas Asociadas</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-40 overflow-y-auto border border-gray-300 rounded-md p-3">
                    @foreach($zonas as $zona)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox"
                                   wire:model="selectedZonas"
                                   value="{{ $zona->id }}"
                                   class="rounded border-gray-300">
                            <span class="text-sm">{{ $zona->nombre }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                @if($editingChatId)
                    <button type="button"
                            wire:click="resetForm"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Cancelar
                    </button>
                @endif
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    {{ $editingChatId ? 'Actualizar' : 'Crear' }}
                </button>
            </div>
        </form>
    </div>

    {{-- Lista de Chats --}}
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Chats Registrados</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Chat ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nombre
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Zonas
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($chats as $chat)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                {{ $chat->chat_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $chat->nombre }}</div>
                                @if($chat->descripcion)
                                    <div class="text-sm text-gray-500">{{ Str::limit($chat->descripcion, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @switch($chat->tipo)
                                    @case('private')
                                        💬 Privado
                                        @break
                                    @case('group')
                                        👥 Grupo
                                        @break
                                    @case('supergroup')
                                        🏢 Supergrupo
                                        @break
                                    @case('channel')
                                        📢 Canal
                                        @break
                                    @default
                                        {{ $chat->tipo }}
                                @endswitch
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $chat->zonas->count() }} zona(s)
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $chat->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $chat->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button wire:click="edit({{ $chat->id }})"
                                        class="text-indigo-600 hover:text-indigo-900">
                                    Editar
                                </button>
                                <button wire:click="delete({{ $chat->id }})"
                                        onclick="return confirm('¿Estás seguro?')"
                                        class="text-red-600 hover:text-red-900">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No hay chats registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $chats->links() }}
        </div>
    </div>
</div>
