@php
    $tarjeta = 'rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-800';
    $botonSecundario = 'rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-none hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700';
    $tipoChat = ['private' => 'Privado', 'group' => 'Grupo', 'supergroup' => 'Grupo', 'channel' => 'Canal'];
    $estado = fn (bool $ok) => $ok
        ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-300'
        : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300';
    $badge = 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset';
@endphp

<div class="w-full space-y-6">
    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Telegram</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Avisos de registros y alertas por chat · {{ $chats->where('activo', true)->count() }} chats activos · {{ $zonasConAviso }} zonas con avisos</p>
        </div>
        <button type="button" wire:click="revisarConexion" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
            <span wire:loading.remove wire:target="revisarConexion">Revisar conexión</span>
            <span wire:loading wire:target="revisarConexion">Revisando…</span>
        </button>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200" role="status">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">{{ session('error') }}</div>
    @endif

    {{-- Configuración --}}
    <section class="{{ $tarjeta }} p-5">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Configuración del servidor</h2>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">Token del bot</dt>
                <dd class="mt-1"><span class="{{ $badge }} {{ $estado($tokenConfigurado) }}">{{ $tokenConfigurado ? 'Configurado' : 'Falta TELEGRAM_BOT_TOKEN' }}</span></dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">Secreto del webhook</dt>
                <dd class="mt-1"><span class="{{ $badge }} {{ $estado($secretoConfigurado) }}">{{ $secretoConfigurado ? 'Configurado' : 'Falta TELEGRAM_WEBHOOK_SECRET' }}</span></dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">Chats de alertas del evento</dt>
                <dd class="mt-1 text-zinc-700 dark:text-zinc-200">{{ $chatsAlertas ? implode(', ', $chatsAlertas) : 'Los chats de las zonas con tema Evento' }}</dd>
            </div>
        </dl>

        @if ($diagnostico)
            <div class="mt-5 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-700">
                @if (isset($diagnostico['error']))
                    <p class="text-red-600 dark:text-red-400">{{ $diagnostico['error'] }}</p>
                @else
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Bot</dt>
                            <dd class="mt-0.5 font-medium text-zinc-900 dark:text-white">{{ $diagnostico['nombre'] }} · {{ '@' . $diagnostico['usuario'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Webhook</dt>
                            <dd class="mt-0.5">
                                @if ($diagnostico['webhook_ok'])
                                    <span class="{{ $badge }} {{ $estado(true) }}">Apunta a este servidor</span>
                                @else
                                    <span class="{{ $badge }} {{ $estado(false) }}">{{ $diagnostico['webhook'] ? 'Apunta a otra dirección' : 'Sin registrar' }}</span>
                                    <span class="mt-1 block text-xs text-zinc-500">Ejecuta <code>php artisan telegram:setup-webhook</code> en el servidor.</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Mensajes en espera</dt>
                            <dd class="mt-0.5 tabular-nums text-zinc-900 dark:text-white">{{ $diagnostico['pendientes'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Último error de Telegram</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-white">{{ $diagnostico['ultimo_error'] ? $diagnostico['ultimo_error'] . ' (' . $diagnostico['ultimo_error_at'] . ')' : 'Ninguno' }}</dd>
                        </div>
                    </dl>
                @endif
            </div>
        @endif
    </section>

    {{-- Chats --}}
    <section class="{{ $tarjeta }} overflow-hidden">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Chats</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Aparecen solos cuando alguien le escribe al bot. Para ligar uno a una zona, genera un código desde el menú ⋯ de la zona (Vincular Telegram) o agrégala aquí.</p>
        </div>

        @if ($chats->isEmpty())
            <p class="px-5 py-12 text-center text-sm text-zinc-500">Ningún chat le ha escrito al bot todavía.</p>
        @else
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                @foreach ($chats as $chat)
                    <li wire:key="chat-{{ $chat->id }}" @class(['px-5 py-4', 'opacity-60' => !$chat->activo])>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $chat->nombre }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $tipoChat[$chat->tipo] ?? $chat->tipo }} · <span class="font-mono">{{ $chat->chat_id }}</span></p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" wire:click="alternarActivo({{ $chat->id }})" class="{{ $botonSecundario }}">{{ $chat->activo ? 'Pausar' : 'Reactivar' }}</button>
                                <button type="button" wire:click="enviarPrueba({{ $chat->id }})" class="{{ $botonSecundario }}">Enviar prueba</button>
                                <button type="button" wire:click="confirmarEliminar({{ $chat->id }})" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-red-600 shadow-none hover:bg-red-50 dark:border-zinc-600 dark:bg-zinc-800">Eliminar</button>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @forelse ($chat->zonas as $zona)
                                <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 py-0.5 pl-2 pr-1 text-xs font-medium text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                                    {{ $zona->nombre }}
                                    <button type="button" wire:click="quitarZona({{ $chat->id }}, {{ $zona->id }})" wire:confirm="¿Dejar de enviar los avisos de {{ $zona->nombre }} a este chat?" class="rounded border-0 bg-transparent px-1 py-0 text-indigo-400 shadow-none hover:text-indigo-700" aria-label="Quitar {{ $zona->nombre }}">×</button>
                                </span>
                            @empty
                                <span class="text-xs text-zinc-400">Sin zonas: no recibe avisos.</span>
                            @endforelse
                            <select wire:model="zonaParaAgregar.{{ $chat->id }}" wire:change="agregarZona({{ $chat->id }})" aria-label="Agregar zona a {{ $chat->nombre }}" class="w-auto rounded-md border border-zinc-300 bg-white py-1 pl-2 pr-7 text-xs text-zinc-600 shadow-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                <option value="">+ Agregar zona</option>
                                @foreach ($zonas->whereNotIn('id', $chat->zonas->pluck('id')) as $zona)
                                    <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Cómo ligar un chat --}}
    <section class="{{ $tarjeta }} p-5">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Cómo ligar un chat a una zona</h2>
        <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-zinc-600 dark:text-zinc-300">
            <li>En Telegram, abre el bot de i-Free (o agrégalo al grupo) y envía <code>/start</code>.</li>
            <li>En <strong>Zonas</strong>, abre el menú ⋯ de la zona → <strong>Vincular Telegram</strong>. El código sirve una vez y vence en 30 minutos.</li>
            <li>En el chat, envía <code>/registrar CODIGO</code>. El bot confirma con el nombre de la zona.</li>
        </ol>
    </section>

    {{-- Confirmar eliminación --}}
    @if ($eliminando)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/50 p-4" wire:keydown.escape.window="$set('eliminandoId', null)">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800" role="dialog" aria-modal="true" aria-labelledby="titulo-eliminar-chat">
                <h2 id="titulo-eliminar-chat" class="text-lg font-semibold text-zinc-900 dark:text-white">¿Eliminar este chat?</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300"><strong>{{ $eliminando->nombre }}</strong> dejará de recibir los avisos de sus {{ $eliminando->zonas()->count() }} zonas.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('eliminandoId', null)" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Cancelar</button>
                    <button type="button" wire:click="eliminar" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-none hover:bg-red-500">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
