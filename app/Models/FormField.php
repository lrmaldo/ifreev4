<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    //
    protected $table = 'form_fields';
    protected $fillable = [
        'zona_id',
        'campo', // nombre, telefono, correo, edad, genero, etc.
        'etiqueta', // Texto visible en el formulario
        'tipo', // text, email, tel, number, select, radio, checkbox
        'obligatorio',
        'orden'
    ];
    protected $casts = [
        'obligatorio' => 'boolean',
        'orden' => 'integer'
    ];
    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }
    public function getTipoOptions()
    {
        return [
            'text' => 'Texto',
            'email' => 'Correo Electrónico',
            'tel' => 'Teléfono',
            'number' => 'Número',
            'select' => 'Seleccionar',
            'radio' => 'Opción Única',
            'checkbox' => 'Casilla de Verificación'
        ];
    }

    /**
     * Relación con las opciones del campo
     */
    public function opciones()
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('orden');
    }

    /**
     * Verifica si un campo tiene opciones (select, radio o checkbox)
     */
    public function tieneOpciones()
    {
        return in_array($this->tipo, ['select', 'radio', 'checkbox']);
    }
}
