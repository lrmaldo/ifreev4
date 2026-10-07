{{-- Menú "⋯" de una zona: lo usan la tabla y las tarjetas del listado --}}
<flux:dropdown position="bottom" align="end">
    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg p-0 shadow-none border border-zinc-300 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700" aria-label="Más acciones de {{ $zona->nombre }}">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 10a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM8.5 10a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM15.5 8.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/></svg>
    </button>

    <flux:menu>
        <flux:menu.group heading="Zona">
            <flux:menu.item icon="pencil-square" :href="route('admin.zonas.editar', ['zonaId' => $zona->id])" wire:navigate>Editar</flux:menu.item>
            <flux:menu.item icon="presentation-chart-bar" :href="route('admin.zonas.configuracion-campanas', ['zonaId' => $zona->id])" wire:navigate>Selección de campañas</flux:menu.item>
            @if ($zona->tipo_registro !== 'sin_registro')
                <flux:menu.item icon="document-text" :href="route('admin.zone.form-fields', ['zonaId' => $zona->id])" wire:navigate>Campos del formulario</flux:menu.item>
                <flux:menu.item icon="chart-bar" :href="route('admin.zona.form-responses', ['zonaId' => $zona->id])" wire:navigate>Respuestas</flux:menu.item>
            @endif
        </flux:menu.group>

        <flux:menu.group heading="Vista previa">
            <flux:menu.item icon="eye" :href="route('cliente.zona.preview', ['id' => $zona->id])" target="_blank">Portal</flux:menu.item>
            <flux:menu.item icon="photo" :href="route('cliente.zona.preview.carrusel', ['id' => $zona->id])" target="_blank">Con imágenes</flux:menu.item>
            <flux:menu.item icon="film" :href="route('cliente.zona.preview.video', ['id' => $zona->id])" target="_blank">Con video</flux:menu.item>
        </flux:menu.group>

        <flux:menu.group heading="Pantalla en vivo">
            @if ($zona->pantalla_token)
                <flux:menu.item icon="tv" :href="route('evento.pantalla', ['token' => $zona->pantalla_token])" target="_blank">Abrir pantalla</flux:menu.item>
                <flux:menu.item icon="arrow-path" wire:click="generarLinkPantalla({{ $zona->id }})" wire:confirm="Las pantallas abiertas con el link actual dejarán de funcionar. ¿Generar un link nuevo?">Regenerar link</flux:menu.item>
            @else
                <flux:menu.item icon="tv" wire:click="generarLinkPantalla({{ $zona->id }})">Crear link de pantalla</flux:menu.item>
            @endif
        </flux:menu.group>

        <flux:menu.group heading="MikroTik">
            <flux:menu.item icon="arrow-down-tray" :href="route('admin.zonas.download', ['zonaId' => $zona->id, 'fileType' => 'login'])">Descargar login.html</flux:menu.item>
            <flux:menu.item icon="arrow-down-tray" :href="route('admin.zonas.download', ['zonaId' => $zona->id, 'fileType' => 'alogin'])">Descargar alogin.html</flux:menu.item>
            <flux:menu.item icon="information-circle" wire:click="openInstructionsModal({{ $zona->id }})">Instrucciones de instalación</flux:menu.item>
        </flux:menu.group>

        <flux:menu.separator />
        <flux:menu.item icon="trash" variant="danger" wire:click="confirmZonaDeletion({{ $zona->id }})">Eliminar zona</flux:menu.item>
    </flux:menu>
</flux:dropdown>
