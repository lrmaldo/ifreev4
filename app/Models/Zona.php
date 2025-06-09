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

    public function respuestas()
    {
        return $this->hasMany(FormResponse::class);
    }

    public function campanas()
    {
        return $this->belongsToMany(Campana::class, 'campana_zona')
                    ->withTimestamps();
    }    public function getCampanasActivas()
    {
        \Log::info("Obteniendo campañas activas para Zona {$this->id}");

        try {
            // Primero obtenemos todas las campañas asociadas a esta zona
            $campanasAsociadas = $this->campanas()->get();

            \Log::info("La zona {$this->id} tiene {$campanasAsociadas->count()} campañas asociadas");

            // Ahora obtenemos todas las campañas activas del sistema
            $campanasActivas = \App\Models\Campana::activas()->get();

            \Log::info("El sistema tiene {$campanasActivas->count()} campañas activas globalmente");

            // Filtramos las campañas asociadas que también son activas
            $campanasActivasDeEstaZona = $campanasAsociadas->filter(function($campana) use ($campanasActivas) {
                return $campanasActivas->contains('id', $campana->id);
            });

            \Log::info("Campañas activas para zona {$this->id}: {$campanasActivasDeEstaZona->count()}");

            // Si no hay campañas activas asociadas, pero sí hay campañas activas globales,
            // creamos las asociaciones automáticamente
            if ($campanasActivasDeEstaZona->isEmpty() && !$campanasActivas->isEmpty()) {
                \Log::info("Creando asociaciones automáticas para la zona {$this->id}");

                foreach ($campanasActivas as $campana) {
                    try {
                        if (!\DB::table('campana_zona')
                                ->where('zona_id', $this->id)
                                ->where('campana_id', $campana->id)
                                ->exists()) {
                            \DB::table('campana_zona')->insert([
                                'zona_id' => $this->id,
                                'campana_id' => $campana->id,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                            \Log::info("Asociada automáticamente: Campaña {$campana->id} a Zona {$this->id}");
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error al crear asociación: " . $e->getMessage());
                    }
                }

                // Actualizamos la colección con las campañas recién asociadas
                $campanasActivasDeEstaZona = $campanasActivas;
            }

            // Registramos información detallada de cada campaña activa
            foreach ($campanasActivasDeEstaZona as $campana) {
                \Log::info("Campaña activa para zona {$this->id} - ID: {$campana->id}, Título: {$campana->titulo}");
            }

            return $campanasActivasDeEstaZona;

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
            \Log::info("No hay campañas de tipo imagen para Zona {$this->id}, usando todas las campañas activas");
            $campanasImagenes = $campanas;
        } else {
            \Log::info("Hay {$campanasImagenes->count()} campañas de tipo imagen para Zona {$this->id}");
        }

        $campanaSeleccionada = null;
        $metodo = $this->seleccion_campanas ?? 'prioridad';

        if ($metodo === 'aleatorio') {
            // Seleccionar una campaña al azar
            $campanaSeleccionada = $campanasImagenes->random();
            \Log::info("Selección aleatoria para Zona {$this->id}: Campaña {$campanaSeleccionada->id}");
        } else {
            // Seleccionar por prioridad (menor número = mayor prioridad)
            // Si prioridad es nula, consideramos que tiene la menor prioridad (99999)
            $campanaSeleccionada = $campanasImagenes->sortBy(function($c) {
                return $c->prioridad ?? 99999;
            })->first();

            \Log::info("Selección por prioridad para Zona {$this->id}: Campaña {$campanaSeleccionada->id} (prioridad: {$campanaSeleccionada->prioridad})");
        }

        // Asegurar que la campaña seleccionada esté asociada correctamente a esta zona
        if ($campanaSeleccionada) {
            try {
                // Verificar si la relación ya existe
                if (!\DB::table('campana_zona')->where('zona_id', $this->id)->where('campana_id', $campanaSeleccionada->id)->exists()) {
                    \DB::table('campana_zona')->insert([
                        'zona_id' => $this->id,
                        'campana_id' => $campanaSeleccionada->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    \Log::info("Creada relación: Zona {$this->id} con Campaña {$campanaSeleccionada->id}");
                }
            } catch (\Exception $e) {
                \Log::error("Error al asociar campaña: " . $e->getMessage());
            }
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
