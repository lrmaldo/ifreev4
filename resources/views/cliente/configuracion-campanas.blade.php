@php
$layout = 'components.layouts.app';
@endphp

<x-dynamic-component :component="$layout">
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
            <flux:button 
                :href="route('cliente.zonas.index')" 
                variant="outline" 
                wire:navigate
            >
                ← Volver a Zonas
            </flux:button>
        </div>

        <!-- Componente Livewire para Configuración de Campañas -->
        @livewire('cliente.configuracion-campanas', ['zonaId' => $zonaId])
    </div>
</x-dynamic-component>