@php
    $editando = (bool) $zonaModelo;
    $urlLogin = rtrim(url('/login_formulario'), '/') . '/';
    $idParaUrl = trim($zona['id_personalizado'] ?? '') ?: ($zonaModelo?->id ?? '{id}');
    $descRegistro = [
        'formulario' => 'La persona llena un formulario (nombre, teléfono…) antes de conectarse.',
        'redes' => 'Registro con redes sociales.',
        'sin_registro' => 'Ve la campaña y se conecta, sin dejar datos.',
    ];
    $descAuth = [
        'sin_autenticacion' => 'Conecta solo al terminar la cuenta regresiva (trial del MikroTik).',
        'usuario_password' => 'Pide usuario y contraseña creados en el MikroTik.',
        'pin' => 'Pide un PIN (usuario del MikroTik).',
    ];
    $input = 'block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100';
    $tarjetaOpcion = 'relative flex cursor-pointer flex-col gap-1 rounded-lg border border-zinc-300 bg-white p-4 text-sm transition hover:border-indigo-400 has-[:checked]:border-indigo-600 has-[:checked]:ring-2 has-[:checked]:ring-indigo-600/20 dark:border-zinc-600 dark:bg-zinc-900';
@endphp

<div class="mx-auto w-full max-w-5xl pb-28">
    {{-- Encabezado --}}
    <div class="mb-8">
        <a href="{{ route('admin.zonas.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-indigo-600 dark:text-zinc-400">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd"/></svg>
            Zonas
        </a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $editando ? $zonaModelo->nombre : 'Nueva zona' }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $editando ? 'Edita la configuración del hotspot y de su portal cautivo.' : 'Configura un hotspot nuevo y cómo se verá su portal cautivo.' }}
                </p>
            </div>
            @if ($editando)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('cliente.zona.preview', ['id' => $zonaModelo->id]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                        Vista previa
                    </a>
                    @if ($zonaModelo->tipo_registro !== 'sin_registro')
                        <a href="{{ route('admin.zone.form-fields', ['zonaId' => $zonaModelo->id]) }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                            Campos del formulario ({{ $zonaModelo->campos()->count() }})
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- 1. Datos generales --}}
        <section class="grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:grid-cols-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Datos generales</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Cómo se identifica la zona y la dirección a la que apunta el login.html del MikroTik.</p>
            </div>
            <div class="space-y-5 lg:col-span-2">
                <div>
                    <label for="nombre" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Nombre</label>
                    <input id="nombre" type="text" wire:model="zona.nombre" class="{{ $input }}" placeholder="Ej. EXPO MX-ISP 2026" autofocus>
                    @error('zona.nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="id_personalizado" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">ID personalizado para la URL <span class="font-normal text-zinc-400">(opcional)</span></label>
                    <input id="id_personalizado" type="text" wire:model.live.debounce.400ms="zona.id_personalizado" class="{{ $input }} font-mono" placeholder="ej. expo-mx-isp">
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        El MikroTik apuntará a <span class="break-all font-mono text-zinc-700 dark:text-zinc-200">{{ $urlLogin }}{{ $idParaUrl }}</span>.
                        Solo letras, números, guiones y guiones bajos; útil para conservar la URL de MikroTiks ya configurados.
                    </p>
                    @error('zona.id_personalizado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- 2. Registro y acceso --}}
        <section class="grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:grid-cols-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Registro y acceso</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Qué le pide el portal a la persona y cómo la conecta el MikroTik.</p>
            </div>
            <div class="space-y-6 lg:col-span-2">
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Tipo de registro</legend>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ($tipoRegistroOptions as $valor => $etiqueta)
                            <label class="{{ $tarjetaOpcion }}">
                                <input type="radio" wire:model.live="zona.tipo_registro" value="{{ $valor }}" class="sr-only">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $etiqueta }}</span>
                                <span class="text-zinc-500 dark:text-zinc-400">{{ $descRegistro[$valor] ?? '' }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('zona.tipo_registro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if (($zona['tipo_registro'] ?? '') === 'formulario')
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                            Para la rifa, el formulario necesita un campo de tipo <strong>Teléfono</strong>.
                            @unless ($editando) Los campos se agregan después de guardar la zona. @endunless
                        </p>
                    @endif
                    @if (($zona['tipo_registro'] ?? '') !== 'sin_registro')
                        <label class="mt-3 flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                            <input type="checkbox" wire:model="zona.login_sin_registro" class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                            Mostrar el botón "No quiero registrarme"
                        </label>
                    @endif
                </fieldset>

                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Autenticación en el MikroTik</legend>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ($tipoAutenticacionMikrotikOptions as $valor => $etiqueta)
                            <label class="{{ $tarjetaOpcion }}">
                                <input type="radio" wire:model="zona.tipo_autenticacion_mikrotik" value="{{ $valor }}" class="sr-only">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $etiqueta }}</span>
                                <span class="text-zinc-500 dark:text-zinc-400">{{ $descAuth[$valor] ?? '' }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('zona.tipo_autenticacion_mikrotik') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>

                <div class="sm:max-w-xs">
                    <label for="segundos" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Cuenta regresiva</label>
                    <div class="flex items-center gap-2">
                        <input id="segundos" type="number" min="5" wire:model="zona.segundos" class="{{ $input }}">
                        <span class="text-sm text-zinc-500">segundos</span>
                    </div>
                    @error('zona.segundos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- 3. Apariencia del portal --}}
        <section class="grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:grid-cols-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Apariencia del portal</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Lo que ve la persona en su celular al conectarse.</p>
                @if ($editando)
                    <a href="{{ route('cliente.zona.preview', ['id' => $zonaModelo->id]) }}" target="_blank" class="mt-3 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-500">Ver vista previa →</a>
                    <p class="mt-1 text-xs text-zinc-400">Muestra lo guardado; guarda primero para ver los cambios.</p>
                @endif
            </div>
            <div class="space-y-5 lg:col-span-2">
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Tema</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="{{ $tarjetaOpcion }}">
                            <input type="radio" wire:model.live="zona.portal_tema" value="clasico" class="sr-only">
                            <span class="flex items-center gap-2 font-medium text-zinc-900 dark:text-white">
                                <span class="flex h-5 w-8 overflow-hidden rounded" aria-hidden="true"><span class="w-1/2 bg-[#ff5e2c]"></span><span class="w-1/2 bg-[#f9fafb] ring-1 ring-inset ring-zinc-200"></span></span>
                                Clásico
                            </span>
                            <span class="text-zinc-500 dark:text-zinc-400">Naranja sobre fondo claro. Para el día a día de los clientes.</span>
                        </label>
                        <label class="{{ $tarjetaOpcion }}">
                            <input type="radio" wire:model.live="zona.portal_tema" value="evento" class="sr-only">
                            <span class="flex items-center gap-2 font-medium text-zinc-900 dark:text-white">
                                <span class="flex h-5 w-8 overflow-hidden rounded" aria-hidden="true"><span class="w-1/2 bg-[#0f2148]"></span><span class="w-1/2 bg-[#f5b82e]"></span></span>
                                Evento
                            </span>
                            <span class="text-zinc-500 dark:text-zinc-400">Azul marino y dorado, con marca del evento opcional.</span>
                        </label>
                    </div>
                    @error('zona.portal_tema') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <label for="portal_mensaje" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Mensaje de bienvenida <span class="font-normal text-zinc-400">(opcional)</span></label>
                    <input id="portal_mensaje" type="text" maxlength="160" wire:model="zona.portal_mensaje" class="{{ $input }}" placeholder="Ej. Bienvenido · WiFi cortesía de i-Free y Sattlink">
                    @error('zona.portal_mensaje') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-3">
                    @if (($zona['portal_tema'] ?? 'clasico') === 'evento')
                        <label class="flex items-start gap-3 text-sm">
                            <input type="checkbox" id="portal_marca_evento" wire:model="zona.portal_marca_evento" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                            <span><span class="font-medium text-zinc-900 dark:text-white">Logos del evento en el encabezado</span><br><span class="text-zinc-500 dark:text-zinc-400">i-Free, WISPMX y "EXPO MX-ISP 2026".</span></span>
                        </label>
                    @endif
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" id="portal_rifa" wire:model="zona.portal_rifa" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                        <span><span class="font-medium text-zinc-900 dark:text-white">Aviso "Regístrate y participa en la rifa"</span><br><span class="text-zinc-500 dark:text-zinc-400">Arriba del formulario, solo para quien aún no se registra.</span></span>
                    </label>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" id="portal_socios" wire:model="zona.portal_socios" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                        <span><span class="font-medium text-zinc-900 dark:text-white">Carrusel de socios al pie</span><br><span class="text-zinc-500 dark:text-zinc-400">Logos de los socios WISPMX en cinta continua.</span></span>
                    </label>
                </div>
            </div>
        </section>

        {{-- 4. Notificaciones --}}
        <section class="grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:grid-cols-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Notificaciones de Telegram</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">A los grupos vinculados a esta zona.</p>
            </div>
            <div class="lg:col-span-2 sm:max-w-md">
                <label for="telegram_resumen_minutos" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Frecuencia</label>
                <select id="telegram_resumen_minutos" wire:model="zona.telegram_resumen_minutos" class="{{ $input }}">
                    <option value="">Al instante (una por dispositivo nuevo)</option>
                    <option value="5">Resumen cada 5 minutos</option>
                    <option value="10">Resumen cada 10 minutos</option>
                    <option value="15">Resumen cada 15 minutos</option>
                    <option value="30">Resumen cada 30 minutos</option>
                    <option value="60">Resumen cada hora</option>
                </select>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Para zonas con mucho tráfico (eventos) usa resumen: Telegram limita los mensajes por minuto.</p>
                @error('zona.telegram_resumen_minutos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- 5. Avanzado --}}
        <details class="group rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-800" @if (($zona['script_head'] ?? '') !== '' || ($zona['script_body'] ?? '') !== '') open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between p-6">
                <span>
                    <span class="block text-base font-semibold text-zinc-900 dark:text-white">Avanzado</span>
                    <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">Scripts de analítica que se insertan en el portal (Google Analytics, píxeles).</span>
                </span>
                <svg class="h-5 w-5 text-zinc-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="grid gap-5 border-t border-zinc-200 p-6 lg:grid-cols-2 dark:border-zinc-700">
                <div>
                    <label for="script_head" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Script en &lt;head&gt;</label>
                    <textarea id="script_head" rows="5" wire:model="zona.script_head" class="{{ $input }} font-mono text-xs"></textarea>
                </div>
                <div>
                    <label for="script_body" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Script al final del &lt;body&gt;</label>
                    <textarea id="script_body" rows="5" wire:model="zona.script_body" class="{{ $input }} font-mono text-xs"></textarea>
                </div>
                <p class="text-xs text-amber-700 lg:col-span-2 dark:text-amber-400">Se ejecutan tal cual en el portal de todos los visitantes: pega solo código de fuentes de confianza.</p>
            </div>
        </details>

        {{-- Barra de acciones fija --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <p class="hidden text-sm text-zinc-500 sm:block dark:text-zinc-400">
                    @if ($errors->any())
                        <span class="text-red-600">Revisa los campos marcados en rojo.</span>
                    @else
                        {{ $editando ? 'Los cambios se aplican al guardar.' : 'Después de guardar podrás agregar campos y campañas.' }}
                    @endif
                </p>
                <div class="flex w-full justify-end gap-3 sm:w-auto">
                    <a href="{{ route('admin.zonas.index') }}" wire:navigate class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Cancelar</a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-60" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $editando ? 'Guardar cambios' : 'Crear zona' }}</span>
                        <span wire:loading wire:target="save">Guardando…</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
