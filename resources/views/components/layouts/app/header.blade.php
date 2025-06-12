<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>

                @hasrole('admin')
                <flux:navbar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                    {{ __('Usuarios') }}
                </flux:navbar.item>
                <flux:navbar.item icon="user-cog" :href="route('admin.roles.index')" :current="request()->routeIs('admin.roles.*')" wire:navigate>
                    {{ __('Roles') }}
                </flux:navbar.item>
                <flux:navbar.item icon="key-square" :href="route('admin.permissions.index')" :current="request()->routeIs('admin.permissions.*')" wire:navigate>
                    {{ __('Permisos') }}
                </flux:navbar.item>
                <flux:navbar.item icon="map-pin" :href="route('admin.zonas.index')" :current="request()->routeIs('admin.zonas.*')" wire:navigate>
                    {{ __('Zonas') }}
                </flux:navbar.item>
                <flux:navbar.item icon="file-text" :href="route('admin.forms.index')" :current="request()->routeIs('admin.forms.*')" wire:navigate>
                    {{ __('Formularios') }}
                </flux:navbar.item>
                <flux:navbar.item icon="users" :href="route('admin.clientes.index')" :current="request()->routeIs('admin.clientes.*')" wire:navigate>
                    {{ __('Clientes') }}
                </flux:navbar.item>
                <flux:navbar.item icon="presentation-chart-bar" :href="route('admin.campanas.index')" :current="request()->routeIs('admin.campanas.*')" wire:navigate>
                    {{ __('Campañas') }}
                </flux:navbar.item>
                <flux:navbar.item icon="chart-bar" :href="route('admin.hotspot-metrics.index')" :current="request()->routeIs('admin.hotspot-metrics.*')" wire:navigate>
                    {{ __('Métricas Hotspot') }}
                </flux:navbar.item>
                <flux:navbar.item icon="chat-bubble-left-right" :href="route('admin.telegram.index')" :current="request()->routeIs('admin.telegram.*')" wire:navigate>
                    {{ __('Telegram') }}
                </flux:navbar.item>
                @endhasrole

                @hasrole('cliente')
                <flux:navbar.item icon="map-pin" :href="route('cliente.zonas.index')" :current="request()->routeIs('cliente.zonas.*')" wire:navigate>
                    {{ __('Mis Zonas') }}
                </flux:navbar.item>
                @can('ver metricas hotspot')
                <flux:navbar.item icon="chart-bar" :href="route('hotspot-metrics.index')" :current="request()->routeIs('hotspot-metrics.*')" wire:navigate>
                    {{ __('Métricas') }}
                </flux:navbar.item>
                @endcan
                @endhasrole

                @hasrole('tecnico')
                @can('ver metricas hotspot')
                <flux:navbar.item icon="chart-bar" :href="route('hotspot-metrics.index')" :current="request()->routeIs('hotspot-metrics.*')" wire:navigate>
                    {{ __('Métricas Hotspot') }}
                </flux:navbar.item>
                @endcan
                @endhasrole
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
            </flux:navbar>

            <!-- Desktop User Menu -->
            <flux:dropdown position="top" align="end">
                <flux:profile
                    class="cursor-pointer"
                    :initials="auth()->user()->initials()"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        @hasrole('admin')
                        <flux:menu.item href="{{ asset('docs/integracion-carrusel-campanas.md') }}" icon="document-text" target="_blank">{{ __('Documentación Campañas') }}</flux:menu.item>
                        @endhasrole
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')">
                    <flux:navlist.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                      {{ __('Dashboard') }}
                    </flux:navlist.item>

                    @hasrole('admin')
                    <flux:navlist.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                        {{ __('Usuarios') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="user-cog" :href="route('admin.roles.index')" :current="request()->routeIs('admin.roles.*')" wire:navigate>
                        {{ __('Roles') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="key-square" :href="route('admin.permissions.index')" :current="request()->routeIs('admin.permissions.*')" wire:navigate>
                        {{ __('Permisos') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="map-pin" :href="route('admin.zonas.index')" :current="request()->routeIs('admin.zonas.*')" wire:navigate>
                        {{ __('Zonas') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="file-text" :href="route('admin.forms.index')" :current="request()->routeIs('admin.forms.*')" wire:navigate>
                        {{ __('Formularios') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="users" :href="route('admin.clientes.index')" :current="request()->routeIs('admin.clientes.*')" wire:navigate>
                        {{ __('Clientes') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="presentation-chart-bar" :href="route('admin.campanas.index')" :current="request()->routeIs('admin.campanas.*')" wire:navigate>
                        {{ __('Campañas') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="chat-bubble-left-right" :href="route('admin.telegram.index')" :current="request()->routeIs('admin.telegram.*')" wire:navigate>
                        {{ __('Telegram') }}
                    </flux:navlist.item>
                    @endhasrole

                    @hasrole('cliente')
                    <flux:navlist.item icon="map-pin" :href="route('cliente.zonas.index')" :current="request()->routeIs('cliente.zonas.*')" wire:navigate>
                        {{ __('Mis Zonas') }}
                    </flux:navlist.item>
                    @endhasrole
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <!-- Se eliminaron los enlaces externos -->
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
