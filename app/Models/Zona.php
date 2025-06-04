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
    }    public function getCampanasActivas()
    {
        $ahora = now(); // Objeto Carbon completo
        $hoy = $ahora->format('Y-m-d'); // Solo fecha para comparaciones de strings
        $diaSemana = strtolower($ahora->locale('es')->dayName);

        // Paso 1: Intentar obtener campañas asociadas a esta zona
        $todasCampanas = $this->campanas()->get();

        // Paso 2: Si no hay relaciones, obtener TODAS las campañas del sistema
        if ($todasCampanas->isEmpty()) {
            $todasCampanas = \App\Models\Campana::all();

            // Crear automáticamente relaciones para futuras consultas
            foreach ($todasCampanas as $campana) {
                \Log::info("Intentando asociar automáticamente: Zona {$this->id} con Campaña {$campana->id}");
                // Crear asociaciones automáticas en la tabla campana_zona para futuras consultas
                if (!\DB::table('campana_zona')->where('zona_id', $this->id)->where('campana_id', $campana->id)->exists()) {
                    try {
                        $this->campanas()->attach($campana->id);
                    } catch (\Exception $e) {
                        \Log::error("Error al asociar campana con zona: " . $e->getMessage());
                    }
                }
            }
        }

        // Filtramos manualmente según los criterios
        $campanasActivas = $todasCampanas->filter(function($campana) use ($ahora, $hoy, $diaSemana) {
            // Para evitar errores, verificamos que los campos necesarios existan
            $fechaInicio = isset($campana->fecha_inicio) ? $campana->fecha_inicio : $ahora;
            $fechaFin = isset($campana->fecha_fin) ? $campana->fecha_fin : $ahora;

            // Extraemos solo la fecha para comparación (sin hora)
            $fechaInicioStr = is_object($fechaInicio) ? $fechaInicio->format('Y-m-d') : substr($fechaInicio, 0, 10);
            $fechaFinStr = is_object($fechaFin) ? $fechaFin->format('Y-m-d') : substr($fechaFin, 0, 10);

            // Comparamos solo la parte de fecha (sin la hora)
            $cumpleFechaInicio = $fechaInicioStr <= $hoy;
            $cumpleFechaFin = $fechaFinStr >= $hoy;
            $visible = (bool)($campana->visible ?? true);

            // Comprobamos la visibilidad por día
            $cumpleDia = $campana->siempre_visible ||
                         (isset($campana->dias_visibles) &&
                          is_array($campana->dias_visibles) &&
                          in_array($diaSemana, $campana->dias_visibles));

            // Si dias_visibles no está definido, consideramos que cumple el criterio
            if (!isset($campana->dias_visibles)) {
                $cumpleDia = true;
            }

            $estaActiva = $visible && $cumpleFechaInicio && $cumpleFechaFin && $cumpleDia;

            return $estaActiva;
        });

        return $campanasActivas;
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
