<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TelegramChat extends Model
{
    protected $fillable = [
        'chat_id',
        'nombre',
        'descripcion',
        'tipo',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación muchos a muchos con zonas
     */
    public function zonas(): BelongsToMany
    {
        return $this->belongsToMany(Zona::class, 'telegram_chat_zona');
    }

    /**
     * Scope para obtener solo los chats activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
