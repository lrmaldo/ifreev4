<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Events\HotspotMetricCreated;

class HotspotMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'zona_id',
        'mac_address',
        'formulario_id',
        'dispositivo',
        'navegador',
        'sistema_operativo',
        'tipo_visual',
        'duracion_visual',
        'clic_boton',
        'veces_entradas',
    ];

    protected $casts = [
        'clic_boton' => 'boolean',
        'veces_entradas' => 'integer',
        'duracion_visual' => 'integer',
    ];

    /**
     * Accesorio para formatear el campo dispositivo cuando parece un user agent
     */
    protected function getDispositivoAttribute($value)
    {
        // Si el valor ya está formateado correctamente, devolverlo tal cual
        if (!str_contains($value, 'Mozilla/5.0')) {
            return $value;
        }

        // Es probablemente un user agent - intentamos extraer información del dispositivo
        $ua = $value;
        $dispositivo = 'Desconocido';

        // Extraer modelo de dispositivo móvil Android
        $regexModelo = '/Android[\s\d\.]+;\s([^;)]+)/i';
        preg_match($regexModelo, $ua, $modeloMatch);

        if (!empty($modeloMatch[1])) {
            $modelo = trim($modeloMatch[1]);
            $dispositivo = $modelo;

            // Detectar y formatear dispositivos Xiaomi/POCO
            if (preg_match('/(M2\d{3}|22\d{6}|21\d{6}|SM-[A-Za-z0-9]+)/', $modelo)) {
                if (stripos($ua, 'poco') !== false) {
                    $dispositivo = "POCO $modelo";
                } elseif (stripos($ua, 'redmi') !== false) {
                    $dispositivo = "Redmi $modelo";
                } elseif (stripos($ua, 'samsung') !== false || str_starts_with($modelo, 'SM-')) {
                    $dispositivo = "Samsung $modelo";
                } elseif (stripos($ua, 'xiaomi') !== false) {
                    $dispositivo = "Xiaomi $modelo";
                }
            }
        }
        // Si es iPhone/iPad
        elseif (str_contains($ua, 'iPhone')) {
            $dispositivo = 'iPhone';
        }
        elseif (str_contains($ua, 'iPad')) {
            $dispositivo = 'iPad';
        }
        // Si es un dispositivo Windows
        elseif (str_contains($ua, 'Windows')) {
            $dispositivo = 'PC Windows';
        }
        // Si es un dispositivo Mac
        elseif (str_contains($ua, 'Macintosh')) {
            $dispositivo = 'Mac';
        }

        return $dispositivo;
    }

    /**
     * Accesorio para formatear el campo navegador cuando parece un user agent
     */
    protected function getNavegadorAttribute($value)
    {
        // Si el valor ya está formateado correctamente, devolverlo tal cual
        if (!str_contains($value, 'Mozilla/5.0')) {
            return $value;
        }

        // Es probablemente un user agent - intentamos extraer información del navegador
        $ua = $value;
        $navegador = 'Desconocido';
        $version = '';

        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg') && !str_contains($ua, 'OPR')) {
            $navegador = 'Chrome';
            preg_match('/Chrome\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'Firefox')) {
            $navegador = 'Firefox';
            preg_match('/Firefox\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) {
            $navegador = 'Safari';
            preg_match('/Version\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'Edg')) {
            $navegador = 'Edge';
            preg_match('/Edg\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'OPR') || str_contains($ua, 'Opera')) {
            $navegador = 'Opera';
            preg_match('/(OPR|Opera)\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[2])) $version = $match[2];
        } elseif (str_contains($ua, 'MIUI')) {
            $navegador = 'Navegador MIUI';
            preg_match('/MiuiBrowser\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'SamsungBrowser')) {
            $navegador = 'Samsung Internet';
            preg_match('/SamsungBrowser\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        }

        if (!empty($version)) {
            $navegador .= ' ' . $version;
        }

        return $navegador;
    }

    /**
     * Accesorio para formatear el campo sistema_operativo cuando contiene datos incompletos
     */
    protected function getSistemaOperativoAttribute($value)
    {
        // Si el valor es null, Win32 o Desconocido, intentamos extraer información de dispositivo o navegador
        if ($value === null || $value === 'Desconocido' || $value === 'Win32') {
            $ua = $this->attributes['dispositivo'] ?? '';

            // Si dispositivo no contiene user agent, intentar con navegador
            if (!str_contains($ua, 'Mozilla/5.0')) {
                $ua = $this->attributes['navegador'] ?? '';
            }

            // Si tenemos un user agent, extraer el sistema operativo
            if (str_contains($ua, 'Android')) {
                preg_match('/Android\s([0-9\.]+)/', $ua, $match);
                return 'Android ' . (!empty($match[1]) ? $match[1] : '');
            } elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') || str_contains($ua, 'iPod')) {
                preg_match('/OS\s([0-9_]+)/', $ua, $match);
                $version = !empty($match[1]) ? str_replace('_', '.', $match[1]) : '';
                return 'iOS ' . $version;
            } elseif (str_contains($ua, 'Windows')) {
                preg_match('/Windows NT\s([0-9\.]+)/', $ua, $match);
                if (!empty($match[1])) {
                    // Mapeo de versiones de Windows NT a nombres comerciales
                    $windowsVersions = [
                        '10.0' => 'Windows 10/11',
                        '6.3' => 'Windows 8.1',
                        '6.2' => 'Windows 8',
                        '6.1' => 'Windows 7',
                        '6.0' => 'Windows Vista',
                        '5.2' => 'Windows XP x64',
                        '5.1' => 'Windows XP',
                        '5.0' => 'Windows 2000'
                    ];
                    return isset($windowsVersions[$match[1]]) ? $windowsVersions[$match[1]] : 'Windows ' . $match[1];
                }
                return 'Windows';
            } elseif (str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh')) {
                preg_match('/Mac OS X\s?([0-9_\.]+)?/', $ua, $match);
                $version = !empty($match[1]) ? str_replace('_', '.', $match[1]) : '';
                return 'macOS ' . $version;
            } elseif (str_contains($ua, 'Linux')) {
                if (str_contains($ua, 'Ubuntu')) {
                    return 'Ubuntu Linux';
                } else if (str_contains($ua, 'Fedora')) {
                    return 'Fedora Linux';
                } else if (str_contains($ua, 'Debian')) {
                    return 'Debian Linux';
                }
                return 'Linux';
            }

            return 'Desconocido';
        }

        return $value;
    }

    /**
     * Relación con la zona
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    /**
     * Relación con el formulario respondido (nullable)
     */
    public function formulario(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class, 'formulario_id');
    }

    /**
     * Relación con los detalles de la métrica
     */
    public function detalles()
    {
        return $this->hasMany(MetricaDetalle::class, 'metrica_id');
    }

    /**
     * Verifica si el usuario llenó el formulario
     */
    public function getRespondioFormularioAttribute(): bool
    {
        return !is_null($this->formulario_id);
    }

    /**
     * Scope para filtrar por zona
     */
    public function scopeByZona($query, $zonaId)
    {
        if ($zonaId) {
            return $query->where('zona_id', $zonaId);
        }
        return $query;
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeByDateRange($query, $from, $to)
    {
        if ($from && $to) {
            return $query->whereBetween('created_at', [$from, $to]);
        }
        if ($from) {
            return $query->where('created_at', '>=', $from);
        }
        if ($to) {
            return $query->where('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Scope para filtrar por MAC address
     */
    public function scopeByMac($query, $mac)
    {
        if ($mac) {
            return $query->where('mac_address', 'like', "%{$mac}%");
        }
        return $query;
    }

    /**
     * Validar y mapear el tipo visual a uno permitido
     */
    public static function validarTipoVisual($tipoVisual)
    {
        $valoresPermitidos = ['formulario', 'carrusel', 'video', 'portal_cautivo', 'portal_entrada', 'login'];

        if (!in_array($tipoVisual, $valoresPermitidos)) {
            // Si es un botón de trial o login, lo mapeamos a 'login'
            if (in_array($tipoVisual, ['trial', 'login'])) {
                return 'login';
            } else {
                // Cualquier otro valor no reconocido lo mapeamos a 'formulario'
                return 'formulario';
            }
        }

        return $tipoVisual;
    }

    /**
     * Registra o actualiza una métrica de hotspot
     */
    public static function registrarMetrica($data)
    {
        // Aseguramos que tipo_visual sea un valor válido
        if (isset($data['tipo_visual'])) {
            $data['tipo_visual'] = self::validarTipoVisual($data['tipo_visual']);
        } else {
            $data['tipo_visual'] = 'formulario'; // Valor por defecto
        }

        $existingMetric = static::where('mac_address', $data['mac_address'])
            ->where('zona_id', $data['zona_id'])
            ->first();

        if ($existingMetric) {
            // Incrementar veces_entradas
            $existingMetric->increment('veces_entradas');

            // Actualizar datos de la visita actual
            $existingMetric->update([
                'dispositivo' => $data['dispositivo'],
                'navegador' => $data['navegador'],
                'sistema_operativo' => $data['sistema_operativo'] ?? null,
                'tipo_visual' => $data['tipo_visual'],
                'duracion_visual' => $data['duracion_visual'] ?? 0,
                'clic_boton' => $data['clic_boton'] ?? false,
                'formulario_id' => $data['formulario_id'] ?? $existingMetric->formulario_id,
            ]);

            return $existingMetric;
        } else {
            // Crear nueva métrica
            return static::create($data);
        }
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (HotspotMetric $hotspotMetric) {
            // Disparar evento cuando se crea una nueva métrica
            HotspotMetricCreated::dispatch($hotspotMetric);
        });
    }
}
