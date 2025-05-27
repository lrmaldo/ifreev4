<div>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Opciones para el campo "{{ $formField->etiqueta }}" ({{ $formField->getTipoOptions()[$formField->tipo] }})
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Administre las opciones disponibles para este campo.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.zone.form-fields', ['zonaId' => $formField->zona_id]) }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-5 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:shadow-outline-indigo active:bg-indigo-700 transition ease-in-out duration-150">
                    Volver a campos
                </a>
            </div>
        </div>

        @if (!$formField->tieneOpciones())
            <div class="p-4 bg-yellow-100 text-yellow-700 rounded m-4">
                Este tipo de campo ({{ $formField->tipo }}) no admite opciones.
            </div>
        @else
            <div class="px-4 py-5 sm:px-6">
                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-700 mb-2">Nueva opción</h4>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Valor</label>
                            <input type="text" wire:model="nuevaOpcion.valor" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                            @error('nuevaOpcion.valor') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Etiqueta</label>
                            <input type="text" wire:model="nuevaOpcion.etiqueta" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                            @error('nuevaOpcion.etiqueta') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-24">
                            <label class="block text-sm font-medium text-gray-700">Orden</label>
                            <input type="number" wire:model="nuevaOpcion.orden" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                            @error('nuevaOpcion.orden') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-end">
                            <button wire:click="agregarOpcion" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Agregar
                            </button>
                        </div>
                    </div>
                </div>

                <h4 class="text-md font-medium text-gray-700 mb-2">Opciones actuales</h4>

                @if (count($opciones) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Etiqueta</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($opciones as $index => $opcion)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="text" wire:model="opciones.{{ $index }}.valor" class="block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            @error("opciones.{$index}.valor") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="text" wire:model="opciones.{{ $index }}.etiqueta" class="block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            @error("opciones.{$index}.etiqueta") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="number" wire:model="opciones.{{ $index }}.orden" class="block w-24 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            @error("opciones.{$index}.orden") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex space-x-2">
                                                <button wire:click="actualizarOpcion({{ $index }})" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                                    Guardar
                                                </button>
                                                <button wire:click="eliminarOpcion({{ $index }})" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 bg-gray-100 text-gray-700 rounded">
                        No hay opciones definidas para este campo.
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
