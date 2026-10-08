{{-- Avatar con iniciales, nombre y correo de un usuario (tabla y tarjetas del listado) --}}
<div class="flex min-w-0 items-center gap-3">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold uppercase text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200" aria-hidden="true">{{ mb_substr($usuario->initials(), 0, 2) }}</span>
    <div class="min-w-0">
        <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
            {{ $usuario->name }}
            @if ($usuario->id === auth()->id())
                <span class="ml-1 rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300">Tú</span>
            @endif
        </p>
        <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $usuario->email }}</p>
    </div>
</div>
