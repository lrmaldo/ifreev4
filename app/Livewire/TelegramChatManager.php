<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TelegramChat;
use App\Models\Zona;
use App\Services\TelegramNotificationService;
use Livewire\WithPagination;

class TelegramChatManager extends Component
{
    use WithPagination;

    public $chat_id = '';
    public $nombre = '';
    public $descripcion = '';
    public $tipo = 'private';
    public $activo = true;
    public $editingChatId = null;
    public $selectedZonas = [];
    public $testChatId = '';
    public $testMessage = '';

    protected $rules = [
        'chat_id' => 'required|string|unique:telegram_chats,chat_id',
        'nombre' => 'required|string|max:255',
        'descripcion' => 'nullable|string',
        'tipo' => 'required|in:private,group,supergroup,channel',
        'activo' => 'boolean',
    ];

    protected $messages = [
        'chat_id.required' => 'El ID del chat es obligatorio.',
        'chat_id.unique' => 'Este chat ya está registrado.',
        'nombre.required' => 'El nombre es obligatorio.',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->chat_id = '';
        $this->nombre = '';
        $this->descripcion = '';
        $this->tipo = 'private';
        $this->activo = true;
        $this->editingChatId = null;
        $this->selectedZonas = [];
        $this->resetValidation();
    }

    public function store()
    {
        if ($this->editingChatId) {
            $this->rules['chat_id'] = 'required|string|unique:telegram_chats,chat_id,' . $this->editingChatId;
        }

        $this->validate();

        try {
            if ($this->editingChatId) {
                $chat = TelegramChat::findOrFail($this->editingChatId);
                $chat->update([
                    'chat_id' => $this->chat_id,
                    'nombre' => $this->nombre,
                    'descripcion' => $this->descripcion,
                    'tipo' => $this->tipo,
                    'activo' => $this->activo,
                ]);
                $message = 'Chat de Telegram actualizado exitosamente.';
            } else {
                $chat = TelegramChat::create([
                    'chat_id' => $this->chat_id,
                    'nombre' => $this->nombre,
                    'descripcion' => $this->descripcion,
                    'tipo' => $this->tipo,
                    'activo' => $this->activo,
                ]);
                $message = 'Chat de Telegram creado exitosamente.';
            }

            // Sincronizar zonas
            if (!empty($this->selectedZonas)) {
                $chat->zonas()->sync($this->selectedZonas);
            }

            session()->flash('message', $message);
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    public function edit($chatId)
    {
        $chat = TelegramChat::with('zonas')->findOrFail($chatId);

        $this->editingChatId = $chat->id;
        $this->chat_id = $chat->chat_id;
        $this->nombre = $chat->nombre;
        $this->descripcion = $chat->descripcion;
        $this->tipo = $chat->tipo;
        $this->activo = $chat->activo;
        $this->selectedZonas = $chat->zonas->pluck('id')->toArray();
    }

    public function delete($chatId)
    {
        try {
            TelegramChat::findOrFail($chatId)->delete();
            session()->flash('message', 'Chat de Telegram eliminado exitosamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    public function testConnection()
    {
        if (empty($this->testChatId)) {
            session()->flash('error', 'Ingresa un Chat ID para probar.');
            return;
        }

        try {
            $telegramService = new TelegramNotificationService();
            $success = $telegramService->testConnection($this->testChatId);

            if ($success) {
                session()->flash('message', 'Conexión exitosa! El bot puede enviar mensajes a este chat.');
            } else {
                session()->flash('error', 'No se pudo conectar al chat. Verifica el token del bot y el Chat ID.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error al probar conexión: ' . $e->getMessage());
        }
    }

    public function getBotInfo()
    {
        try {
            $telegramService = new TelegramNotificationService();
            $botInfo = $telegramService->getBotInfo();

            if ($botInfo) {
                $this->testMessage = "Bot Info: @{$botInfo['username']} (ID: {$botInfo['id']})";
            } else {
                $this->testMessage = "No se pudo obtener información del bot.";
            }
        } catch (\Exception $e) {
            $this->testMessage = "Error: " . $e->getMessage();
        }
    }

    public function render()
    {
        $chats = TelegramChat::with('zonas')->paginate(10);
        $zonas = Zona::all();

        return view('livewire.telegram-chat-manager', [
            'chats' => $chats,
            'zonas' => $zonas,
        ]);
    }
}
