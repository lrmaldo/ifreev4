<x-layouts.app :title="'Gestión de Zonas'">
    <x-layouts.module-header
        title="Gestión de Zonas"
        description="Administra las zonas de tu sistema"
        icon="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"
    >
        <x-slot:actions>

        </x-slot:actions>
    </x-layouts.module-header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Contenido del componente Livewire -->
            @livewire('admin.zonas.index')
        </div>
    </div>
</x-layouts.app>
