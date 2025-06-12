<x-layouts.app>
    <x-slot:title>Gestión de Telegram</x-slot:title>

    <flux:header container class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Gestión de Telegram') }}</flux:heading>
        <flux:badge color="emerald" size="sm">Sistema Activo</flux:badge>
    </flux:header>

    <flux:main container class="space-y-6">
        <!-- Panel de Estado -->
        <flux:card class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-md">
                            <flux:icon.chat-bubble-left-right class="w-6 h-6 text-blue-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Bot Status</p>
                            <p class="text-lg font-semibold text-gray-900" id="bot-status">Configurado</p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-md">
                            <flux:icon.users class="w-6 h-6 text-green-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Chats Registrados</p>
                            <p class="text-lg font-semibold text-gray-900" id="chats-count">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-md">
                            <flux:icon.map-pin class="w-6 h-6 text-purple-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Zonas Asociadas</p>
                            <p class="text-lg font-semibold text-gray-900" id="zonas-count">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-md">
                            <flux:icon.information-circle class="w-6 h-6 text-yellow-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Webhook Status</p>
                            <p class="text-lg font-semibold text-gray-900">Configurado</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instrucciones Rápidas -->
            <flux:card class="bg-blue-50 border border-blue-200">
                <flux:card.body>
                    <flux:heading size="lg" class="text-blue-900 mb-2">🚀 Instrucciones Rápidas</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
                        <div>
                            <h4 class="font-semibold mb-1">Para empezar:</h4>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Los chats se registran automáticamente</li>
                                <li>Los usuarios envían <code>/start</code> al bot</li>
                                <li>Usan <code>/registrar [zona_id]</code> para asociarse</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1">Webhook URL:</h4>
                            <p class="bg-white p-2 rounded border font-mono text-xs">
                                https://v3.i-free.com.mx/telegram/webhook
                            </p>
                        </div>
                    </div>
                </flux:card.body>
            </flux:card>
        </flux:card>

        <!-- Componente Principal -->
        <flux:card>
            <flux:card.body>
                <livewire:telegram-chat-manager />
            </flux:card.body>
        </flux:card>

        <!-- Panel de Comandos Útiles -->
        <flux:card>
            <flux:card.body>
                <flux:heading size="lg" class="mb-4">🛠️ Comandos Útiles</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">Verificar Estado</h4>
                        <code class="text-sm bg-gray-800 text-green-400 p-2 rounded block">php artisan telegram:status</code>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">Probar Notificaciones</h4>
                        <code class="text-sm bg-gray-800 text-green-400 p-2 rounded block">php artisan telegram:test --zona_id=1</code>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">Probar Webhook</h4>
                        <code class="text-sm bg-gray-800 text-green-400 p-2 rounded block">php artisan telegram:test-webhook [chat_id]</code>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">Ejecutar Colas</h4>
                        <code class="text-sm bg-gray-800 text-green-400 p-2 rounded block">php artisan queue:work</code>
                    </div>
                </div>
            </flux:card.body>
        </flux:card>
    </flux:main>

    @push('scripts')
    <script>
        // Actualizar estadísticas dinámicamente
        document.addEventListener('DOMContentLoaded', function() {
            // Aquí puedes agregar código para actualizar las estadísticas via AJAX
            // Por ahora, las estadísticas se muestran estáticamente
        });
    </script>
    @endpush
</x-layouts.app>
