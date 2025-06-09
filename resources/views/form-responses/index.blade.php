<x-layouts.app>
    <x-slot:title>Respuestas del Formulario</x-slot:title>

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('dashboard') }}" icon="home">Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ auth()->user()->hasRole('admin') ? route('admin.zonas.index') : route('cliente.zonas.index') }}">Zonas</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Respuestas del Formulario</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <!-- Componente Livewire -->
            @livewire('form-responses-list', ['zona' => $zona])
        </div>
    </div>
</x-layouts.app>
