<div class="flex flex-col gap-6">
    <x-auth-header title="Crear una cuenta" description="Ingresa tus datos para crear tu cuenta" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        <flux:input
            wire:model="name"
            label="Nombre completo"
            type="text"
            required
            autofocus
            autocomplete="name"
            placeholder="Tu nombre completo"
        />

        <!-- Email Address -->
        <flux:input
            wire:model="email"
            label="Correo electrónico"
            type="email"
            required
            autocomplete="email"
            placeholder="correo@ejemplo.com"
        />

        <!-- Password -->
        <flux:input
            wire:model="password"
            label="Contraseña"
            type="password"
            required
            autocomplete="new-password"
            placeholder="Tu contraseña"
            viewable
        />

        <!-- Confirm Password -->
        <flux:input
            wire:model="password_confirmation"
            label="Confirmar contraseña"
            type="password"
            required
            autocomplete="new-password"
            placeholder="Confirma tu contraseña"
            viewable
        />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                Crear cuenta
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        ¿Ya tienes una cuenta?
        <flux:link :href="route('login')" wire:navigate>Inicia sesión</flux:link>
    </div>
</div>
