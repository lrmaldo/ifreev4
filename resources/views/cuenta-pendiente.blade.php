<x-layouts.auth :title="'Cuenta en revisión'">
    <div class="flex flex-col gap-5 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-500/15 dark:text-orange-300">
            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">Tu cuenta está en revisión</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                Gracias por registrarte, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}.
                El equipo de Sattlink revisará tu solicitud y te dará acceso a la plataforma. Te contactaremos a <strong>{{ auth()->user()->email }}</strong>.
            </p>
        </div>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">¿Tienes prisa? Escríbenos por WhatsApp desde la página principal.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-none hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Cerrar sesión</button>
        </form>
    </div>
</x-layouts.auth>
