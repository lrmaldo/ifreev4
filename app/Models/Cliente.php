<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes'; // Specify the table name if it's different from the default
    protected $fillable = [
        'razon social',
        'rfc',
        'telefono',
        'correo',
        'direccion',
        'nombre_comercial'
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    protected $primaryKey = 'id'; // Specify the primary key if it's different from the default
}
