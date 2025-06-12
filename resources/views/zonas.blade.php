<x-layouts.app :title="'Gestión de Zonas'">
    <x-layouts.module-header
        title="Gestión de Zonas"
        description="Administra las zonas de tu sistema"
        icon="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"
    >
        <x-slot:actions>
            {{-- <button
                onclick="window.openNewZona ? window.openNewZona() : window.openZonaModal()"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-zinc-800"
                title="Nueva Zona"
            >
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Nueva Zona
            </button> --}}
        </x-slot:actions>
    </x-layouts.module-header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Contenido del componente Livewire -->
            @livewire('admin.zonas.index')
        </div>
    </div>
</x-layouts.app>
