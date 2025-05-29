<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col md:flex-row items-center justify-center gap-6 p-6 md:p-0">
            <!-- Carrusel de campañas en la mitad izquierda (solo en md y superior) -->
            <div class="hidden md:flex md:w-1/2 h-full">
                <div class="w-full h-screen">
                    @livewire('auth.carrusel-campanas')
                </div>
            </div>

            <!-- Panel de autenticación en la mitad derecha -->
            <div class="flex w-full md:w-1/2 justify-center items-center md:p-10">
                <div class="flex w-full max-w-sm flex-col gap-2">
                    <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                        <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                        </span>
                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    <!-- Carrusel de campañas para móviles (solo en tamaños xs-sm) -->
                    <div class="md:hidden w-full h-48 mb-6 rounded-lg overflow-hidden shadow-sm">
                        @livewire('auth.carrusel-campanas')
                    </div>

                    <div class="flex flex-col gap-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
