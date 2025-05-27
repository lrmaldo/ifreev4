<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormFieldOption extends Model
{
    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'form_field_id',
        'valor',
        'etiqueta',
        'orden',
    ];

    /**
     * Relación con el campo del formulario
     */
    public function formField()
    {
        return $this->belongsTo(FormField::class);
    }
}
