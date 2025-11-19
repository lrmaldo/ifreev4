@php
$layout = 'components.layouts.app';
@endphp

<x-dynamic-component :component="$layout">
    <div class="space-y-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Mis Zonas</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Gestiona tus zonas de portal cautivo y descarga los archivos para Mikrotik
            </p>
        </div>

        <!-- Componente Livewire para Zonas de Cliente -->
        @livewire('cliente.zonas-index')
    </div>
</x-dynamic-component>
