<x-layouts.app>
    <div class="space-y-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Mis Campañas</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Gestiona las campañas publicitarias de tus zonas WiFi
            </p>
        </div>

        <!-- Componente Livewire para Campañas de Cliente -->
        @livewire('cliente.campanas-index')
    </div>
</x-layouts.app>
