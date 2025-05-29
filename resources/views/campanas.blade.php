<x-layouts.app :title="'Gestión de Campañas'">
    <x-layouts.module-header
        title="Gestión de Campañas"
        description="Administra el carrusel de imágenes y videos para el portal de acceso"
        icon="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
    >
        <x-slot:actions>
            <button
                onclick="Livewire.dispatch('openCampanaModal')"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-zinc-800"
            >
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Nueva Campaña
            </button>
        </x-slot:actions>
    </x-layouts.module-header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @livewire('admin.campanas.index')
        </div>
    </div>
</x-layouts.app>
