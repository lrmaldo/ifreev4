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
        'tiempo_visualizacion',
        'telegram_resumen_minutos',
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
        'telegram_resumen_minutos' => 'integer',
        'telegram_ultimo_resumen_at' => 'datetime',
    ];

    /**
     * Valores permitidos para seleccion_campanas:
     * - aleatorio: Alterna automáticamente entre videos e imágenes
     * - prioridad: Selecciona campañas basado en el valor numérico de prioridad (menor = mayor prioridad)
     * - video: Muestra solo videos (si hay disponibles)
     * - imagen: Muestra solo imágenes (si hay disponibles)
     */
    public function getOptionsSeleccionCampanas()
    {
        return [
            'aleatorio' => 'Alternancia automática',
            'prioridad' => 'Por prioridad',
            'video' => 'Solo videos',
            'imagen' => 'Solo imágenes',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function campos()
    {
        return $this->hasMany(FormField::class, 'zona_id');
    }

    public function respuestas()
    {
        return $this->hasMany(FormResponse::class);
    }

    public function campanas()
    {
        return $this->belongsToMany(Campana::class, 'campana_zona')
                    ->withTimestamps();
    }

    public function metricas()
    {
        return $this->hasMany(HotspotMetric::class);
    }

    /**
     * Relación muchos a muchos con chats de Telegram
     */
    public function telegramChats()
    {
        return $this->belongsToMany(TelegramChat::class, 'telegram_chat_zona')
                    ->withTimestamps();
    }

    /**
     * Campañas activas de la zona (asignadas, o las del cliente/globales como respaldo).
     * Ver App\Services\SeleccionCampanaService::campanasActivas().
     */
    public function getCampanasActivas()
    {
        try {
            return app(\App\Services\SeleccionCampanaService::class)->campanasActivas($this);
        } catch (\Exception $e) {
            \Log::error("Error al obtener campañas activas: " . $e->getMessage());
            return collect([]);
        }
    }

    public function getCampanaSeleccionada()
    {
        $campanas = $this->getCampanasActivas();

        if ($campanas->isEmpty()) {
            \Log::warning("No hay campañas activas para la Zona {$this->id}, no se puede seleccionar ninguna");
            return null;
        }

        // Filtrar solo campañas de tipo imagen si hay alguna
        $campanasImagenes = $campanas->filter(function($campana) {
            $tipo = strtolower($campana->tipo ?? '');
            return empty($tipo) || $tipo === 'imagen' || $tipo === 'imagenes' ||
                  $tipo === 'image' || $tipo === 'img' || strpos($tipo, 'imag') !== false;
        });

        // Si no hay campañas de tipo imagen, usamos todas las campañas
        if ($campanasImagenes->isEmpty()) {
            \Log::debug("No hay campañas de tipo imagen para Zona {$this->id}, usando todas las campañas activas");
            $campanasImagenes = $campanas;
        } else {
            \Log::debug("Hay {$campanasImagenes->count()} campañas de tipo imagen para Zona {$this->id}");
        }

        $campanaSeleccionada = null;
        $metodo = $this->seleccion_campanas ?? 'prioridad';

        if ($metodo === 'aleatorio') {
            // Seleccionar una campaña al azar
            $campanaSeleccionada = $campanasImagenes->random();
            \Log::debug("Selección aleatoria para Zona {$this->id}: Campaña {$campanaSeleccionada->id}");
        } else {
            // Seleccionar por prioridad (menor número = mayor prioridad)
            // Si prioridad es nula, consideramos que tiene la menor prioridad (99999)
            $campanaSeleccionada = $campanasImagenes->sortBy(function($c) {
                return $c->prioridad ?? 99999;
            })->first();

            \Log::debug("Selección por prioridad para Zona {$this->id}: Campaña {$campanaSeleccionada->id} (prioridad: {$campanaSeleccionada->prioridad})");
        }

        return $campanaSeleccionada;
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
            'usuario_password' => 'Usuario y Contraseña',
            'sin_autenticacion' => 'Sin Autenticación'
        ];
    }

    public function getTipoAutenticacionMikrotikLabelAttribute()
    {
        return $this->getTipoAutenticacionMikrotikOptions()[$this->tipo_autenticacion_mikrotik] ?? 'PIN';
    }

    /**
     * Verifica si la zona requiere autenticación Mikrotik
     *
     * @return bool
     */
    public function getRequiereAutenticacionMikrotikAttribute()
    {
        return $this->tipo_autenticacion_mikrotik !== 'sin_autenticacion';
    }

    /**
     * Todas las zonas están activas por defecto en este sistema.
     * No existe campo de estado en la tabla.
     *
     * @return bool
     */
    public function getActivoAttribute()
    {
        return true;
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
