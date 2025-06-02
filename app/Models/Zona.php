<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zona extends Model
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
        'script_body',
        'seleccion_campanas',
        'tiempo_visualizacion'
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
        'seleccion_campanas' => 'string',
        'tiempo_visualizacion' => 'integer',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function campos()
    {
        return $this->hasMany(FormField::class, 'zona_id');
    }

    public function campanas()
    {
        return $this->belongsToMany(Campana::class, 'campana_zona')
                    ->withTimestamps();
    }

    public function getCampanasActivas()
    {
        $hoy = now()->format('Y-m-d');
        $diaSemana = strtolower(now()->locale('es')->dayName);

        $query = $this->campanas()
            ->where('visible', true)
            ->where(function($q) use ($hoy, $diaSemana) {
                $q->where(function($q) use ($hoy) {
                    $q->where('fecha_inicio', '<=', $hoy)
                      ->where('fecha_fin', '>=', $hoy)
                      ->where('siempre_visible', true);
                })
                ->orWhere(function($q) use ($hoy, $diaSemana) {
                    $q->where('fecha_inicio', '<=', $hoy)
                      ->where('fecha_fin', '>=', $hoy)
                      ->where('siempre_visible', false)
                      ->whereJsonContains('dias_visibles', $diaSemana);
                });
            });

        return $query->get();
    }

    public function getCampanaSeleccionada()
    {
        $campanas = $this->getCampanasActivas();

        if ($campanas->isEmpty()) {
            return null;
        }

        if ($this->seleccion_campanas === 'aleatorio') {
            // Seleccionar una campaña al azar
            return $campanas->random();
        } else {
            // Seleccionar por prioridad (menor número = mayor prioridad)
            return $campanas->sortBy('prioridad')->first();
        }
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
