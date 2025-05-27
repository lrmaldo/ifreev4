<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Zona extends Pivot
{
    //
    protected $table = 'zonas';
    protected $fillable = [
        'nombre',
        'id_analytics',
        'id_personalizado',
        'user_id',
        'requiere_registro',
        'campo_nombre',
        'campo_telefono',
        'campo_correo',
        'campo_edad',
        'campo_genero',
        'campo_mac_address',
        'segundos',
        'tipo_registro',
        'login_sin_registro',
        'tipo_autenticacion_mikrotik',
        'script_head',
        'script_body'
    ];
    protected $casts = [
        'requiere_registro' => 'boolean',
        'campo_nombre' => 'boolean',
        'campo_telefono' => 'boolean',
        'campo_correo' => 'boolean',
        'campo_edad' => 'boolean',
        'campo_genero' => 'boolean',
        'campo_mac_address' => 'boolean',
        'segundos' => 'integer',
        'tipo_registro' => 'string',
        'login_sin_registro' => 'boolean',
        'tipo_autenticacion_mikrotik' => 'string',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function campos()
    {
        return $this->hasMany(FormField::class, 'zona_id');
    }
    public function getTipoRegistroOptions()
    {
        return [
            'formulario' => 'Formulario',
            'redes' => 'Redes Sociales',
            'sin_registro' => 'Sin Registro'
        ];
    }
    public function getTipoRegistroLabelAttribute()
    {
        return $this->getTipoRegistroOptions()[$this->tipo_registro] ?? 'Desconocido';
    }

    public function getTipoAutenticacionMikrotikOptions()
    {
        return [
            'pin' => 'PIN',
            'usuario_password' => 'Usuario y Contraseña'
        ];
    }

    public function getTipoAutenticacionMikrotikLabelAttribute()
    {
        return $this->getTipoAutenticacionMikrotikOptions()[$this->tipo_autenticacion_mikrotik] ?? 'PIN';
    }

    /**
     * Obtiene el ID que se usará en los formularios de login para Mikrotik.
     * Usa el ID personalizado si está definido, de lo contrario usa el ID real.
     *
     * @return mixed
     */
    public function getLoginFormIdAttribute()
    {
        return $this->id_personalizado ?? $this->id;
    }
}
