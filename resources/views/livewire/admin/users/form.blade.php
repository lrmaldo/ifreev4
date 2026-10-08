@php
    $editando = (bool) $usuario;
    $descRol = [
        'admin' => 'Acceso total: zonas, campañas, clientes, usuarios y configuración.',
        'cliente' => 'Ve y configura solo sus propias zonas, campañas y métricas.',
        'tecnico' => 'Consulta y exporta métricas de los hotspots.',
    ];
    $input = 'block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100';
    $tarjetaOpcion = 'relative flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-300 bg-white p-4 text-sm transition hover:border-indigo-400 has-[:checked]:border-indigo-600 has-[:checked]:ring-2 has-[:checked]:ring-indigo-600/20 dark:border-zinc-600 dark:bg-zinc-900';
@endphp

<div class="mx-auto w-full max-w-4xl pb-28">
    {{-- Encabezado --}}
    <div class="mb-8">
        <a href="{{ route('admin.users.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-indigo-600 dark:text-zinc-400">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd"/></svg>
            Usuarios
        </a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $editando ? $usuario->name : 'Nuevo usuario' }}</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            @if ($editando)
                Alta el {{ $usuario->created_at?->translatedFormat('j \d\e F \d\e Y') }}
                @if ($zonas->isNotEmpty()) · {{ $zonas->count() }} {{ $zonas->count() === 1 ? 'zona' : 'zonas' }} a su nombre @endif
            @else
                Crea un acceso al panel y define qué puede hacer.
            @endif
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- 1. Datos de acceso --}}
        <section class="grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:grid-cols-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Datos de acceso</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Con el correo y la contraseña entra al panel.</p>
            </div>
            <div class="space-y-5 lg:col-span-2">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Nombre</label>
                    <input id="name" type="text" wire:model="name" class="{{ $input }}" autocomplete="off" placeholder="Nombre y apellido" @unless($editando) autofocus @endunless>
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Correo</label>
                    <input id="email" type="email" wire:model="email" class="{{ $input }}" autocomplete="off" placeholder="correo@empresa.com">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div x-data="{ ver: false }">
                    <label for="password" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ $editando ? 'Nueva contraseña' : 'Contraseña' }}
                    </label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input id="password" :type="ver ? 'text' : 'password'" wire:model="password" class="{{ $input }} pr-16 font-mono" autocomplete="new-password" placeholder="{{ $editando ? 'Déjala vacía para no cambiarla' : 'Mínimo 8 caracteres' }}">
                            <button type="button" x-on:click="ver = !ver" class="absolute inset-y-0 right-0 border-0 bg-transparent px-3 text-xs font-medium text-zinc-500 shadow-none hover:text-zinc-700" x-text="ver ? 'Ocultar' : 'Ver'"></button>
                        </div>
                        <button type="button" wire:click="generarPassword" x-on:click="ver = true" class="shrink-0 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-none hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Generar</button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Si la generas, cópiala antes de guardar: después no se puede volver a ver.</p>
                    @enderror
                </div>
            </div>
        </section>

        {{-- 2. Rol --}}
        <section class="grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:grid-cols-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Rol</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Define qué secciones del panel puede ver. Un usuario sin rol no puede usar el panel.</p>
            </div>
            <div class="space-y-3 lg:col-span-2">
                @foreach ($rolesDisponibles as $rol)
                    <label class="{{ $tarjetaOpcion }}" wire:key="rol-{{ $rol->id }}">
                        <input type="checkbox" wire:model.live="roles" value="{{ $rol->name }}" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                        <span>
                            <span class="block font-medium text-zinc-900 dark:text-white">{{ ucfirst($rol->name) }}</span>
                            <span class="mt-0.5 block text-zinc-500 dark:text-zinc-400">{{ $descRol[$rol->name] ?? 'Rol personalizado: ' . $rol->permissions()->count() . ' permisos.' }}</span>
                        </span>
                    </label>
                @endforeach
                @error('roles') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @if (empty($roles))
                    <p class="text-sm text-amber-700 dark:text-amber-400">Sin rol, esta persona podrá iniciar sesión pero no verá ninguna sección.</p>
                @endif
            </div>
        </section>

        {{-- 3. Cliente --}}
        <section class="grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:grid-cols-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Empresa</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">El cliente (empresa) al que pertenece. Sirve para agrupar a los usuarios de un mismo cliente.</p>
            </div>
            <div class="space-y-5 lg:col-span-2">
                <div>
                    <label for="cliente_id" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Cliente</label>
                    <select id="cliente_id" wire:model="cliente_id" class="{{ $input }}">
                        <option value="">Ninguno (Sattlink / i-Free)</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                @if ($zonas->isNotEmpty())
                    <div>
                        <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Zonas a su nombre</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($zonas as $zona)
                                <a href="{{ route('admin.zonas.editar', ['zonaId' => $zona->id]) }}" wire:navigate class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 hover:bg-indigo-50 hover:text-indigo-700 dark:bg-zinc-700 dark:text-zinc-200">{{ $zona->nombre }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- Barra de acciones fija --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex w-full max-w-4xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <p class="hidden text-sm text-zinc-500 sm:block dark:text-zinc-400">
                    @if ($errors->any())
                        <span class="text-red-600">Revisa los campos marcados en rojo.</span>
                    @else
                        {{ $editando ? 'Los cambios se aplican al guardar.' : 'Podrá entrar en cuanto lo guardes.' }}
                    @endif
                </p>
                <div class="flex w-full justify-end gap-3 sm:w-auto">
                    <a href="{{ route('admin.users.index') }}" wire:navigate class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Cancelar</a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-60" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $editando ? 'Guardar cambios' : 'Crear usuario' }}</span>
                        <span wire:loading wire:target="save">Guardando…</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
