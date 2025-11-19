<x-layouts.app>
    <div class="space-y-6">
        <!-- Encabezado -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Configuración de Campañas</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Gestiona las campañas publicitarias para esta zona WiFi
                </p>
            </div>

            <!-- Botón volver -->
            <a href="{{ route('cliente.zonas.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg transition-colors"
               wire:navigate>
                ← Volver a Zonas
            </a>
        </div>

        <!-- Componente Livewire para Configuración de Campañas -->
        @livewire('cliente.configuracion-campanas', ['zonaId' => $zonaId])
    </div>
</x-layouts.app>
