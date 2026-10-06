<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RifaGanador extends Model
{
    protected $table = 'rifa_ganadores';

    protected $fillable = ['zona_id', 'form_response_id', 'nombre', 'telefono_final', 'premio'];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function formResponse()
    {
        return $this->belongsTo(FormResponse::class);
    }
}
