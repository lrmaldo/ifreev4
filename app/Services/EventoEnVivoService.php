<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\MetricaDetalle;
use App\Models\RifaGanador;
use App\Models\Zona;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Datos de la pantalla en vivo de una zona (aforo, registros, anuncios) y la rifa.
 */
class EventoEnVivoService
{
    /** Minutos sin actividad tras los cuales un dispositivo deja de contar como "conectado ahora" */
    public const MINUTOS_ACTIVO = 15;

    public function estadisticas(Zona $zona): array
    {
        $inicioDia = now()->startOfDay();
        $metricasHoy = HotspotMetric::where('zona_id', $zona->id)->where('updated_at', '>=', $inicioDia);

        $dispositivosHoy = (clone $metricasHoy)->count();
        $android = (clone $metricasHoy)->where('sistema_operativo', 'like', 'Android%')->count();
        $iphone = (clone $metricasHoy)->where('sistema_operativo', 'like', 'iOS%')->count();

        $detallesHoy = MetricaDetalle::whereIn('metrica_id', HotspotMetric::select('id')->where('zona_id', $zona->id))
            ->where('fecha_hora', '>=', $inicioDia);

        $registrosHoy = FormResponse::where('zona_id', $zona->id)->where('created_at', '>=', $inicioDia)->count();

        return [
            'conectados' => HotspotMetric::where('zona_id', $zona->id)
                ->where('updated_at', '>=', now()->subMinutes(self::MINUTOS_ACTIVO))
                ->count(),
            'dispositivosHoy' => $dispositivosHoy,
            'registrosHoy' => $registrosHoy,
            'registrosTotal' => FormResponse::where('zona_id', $zona->id)->count(),
            'impresionesHoy' => (clone $detallesHoy)->where('tipo_evento', 'vista')->count(),
            'clicsHoy' => (clone $detallesHoy)->where('tipo_evento', 'clic')->count(),
            'plataformas' => [
                'android' => $android,
                'iphone' => $iphone,
                'otros' => max(0, $dispositivosHoy - $android - $iphone),
            ],
            'llegadasPorHora' => $this->llegadasPorHora($zona),
            'ultimosRegistros' => $this->ultimosRegistros($zona),
        ];
    }

    /**
     * Dispositivos nuevos de hoy por hora, desde la primera hora con llegadas hasta la hora actual.
     *
     * @return array<int, array{hora: string, total: int}>
     */
    public function llegadasPorHora(Zona $zona): array
    {
        $porHora = HotspotMetric::where('zona_id', $zona->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->pluck('created_at')
            ->countBy(fn ($fecha) => (int) $fecha->format('G'));

        if ($porHora->isEmpty()) {
            return [];
        }

        $desde = min($porHora->keys()->min(), (int) now()->format('G'));
        $hasta = (int) now()->format('G');

        return collect(range($desde, $hasta))
            ->map(fn ($h) => ['hora' => sprintf('%02d:00', $h), 'total' => $porHora->get($h, 0)])
            ->all();
    }

    /**
     * Últimos registros para el "feed" de la pantalla: solo nombre corto, nunca datos de contacto.
     */
    public function ultimosRegistros(Zona $zona, int $limite = 6): array
    {
        $campoNombre = $this->campoNombre($zona);

        return FormResponse::where('zona_id', $zona->id)
            ->latest()
            ->limit($limite)
            ->get()
            ->map(fn (FormResponse $r) => [
                'id' => $r->id,
                'nombre' => $this->nombreCorto($campoNombre ? ($r->respuestas[$campoNombre] ?? null) : null),
                'hace' => $r->created_at->locale('es')->diffForHumans(short: true),
            ])
            ->all();
    }

    /**
     * Registros que pueden ganar: con teléfono válido y sin premio previo en la zona.
     */
    public function participantes(Zona $zona): Collection
    {
        $campoTelefono = $this->campoTelefono($zona);
        if (!$campoTelefono) {
            return collect();
        }
        $campoNombre = $this->campoNombre($zona);
        $ganadores = RifaGanador::where('zona_id', $zona->id)->pluck('form_response_id');

        return FormResponse::where('zona_id', $zona->id)
            ->whereNotIn('id', $ganadores)
            ->get()
            ->map(function (FormResponse $r) use ($campoNombre, $campoTelefono) {
                $digitos = preg_replace('/\D/', '', (string) ($r->respuestas[$campoTelefono] ?? ''));

                return [
                    'id' => $r->id,
                    'nombre' => $this->nombreCorto($campoNombre ? ($r->respuestas[$campoNombre] ?? null) : null),
                    'telefono_final' => strlen($digitos) >= 4 ? substr($digitos, -4) : null,
                ];
            })
            ->filter(fn ($p) => $p['telefono_final'] !== null)
            ->values();
    }

    /**
     * Elige un ganador al azar y lo guarda.
     *
     * @return array{ganador: ?RifaGanador, nombres: array<int, string>, participantes: int}
     */
    public function sortear(Zona $zona, ?string $premio = null): array
    {
        $participantes = $this->participantes($zona);

        if ($participantes->isEmpty()) {
            return ['ganador' => null, 'nombres' => [], 'participantes' => 0];
        }

        $elegido = $participantes->random();
        $ganador = RifaGanador::create([
            'zona_id' => $zona->id,
            'form_response_id' => $elegido['id'],
            'nombre' => $elegido['nombre'],
            'telefono_final' => $elegido['telefono_final'],
            'premio' => $premio ? Str::limit(trim($premio), 120, '') : null,
        ]);

        // Nombres para la animación del sorteo (no incluyen teléfonos)
        $nombres = $participantes->shuffle()->take(30)->pluck('nombre')->push($elegido['nombre'])->values()->all();

        return ['ganador' => $ganador, 'nombres' => $nombres, 'participantes' => $participantes->count()];
    }

    /** "ana maría garcía lópez" → "Ana G." */
    public function nombreCorto(?string $nombre): string
    {
        $partes = preg_split('/\s+/', trim((string) $nombre), -1, PREG_SPLIT_NO_EMPTY);

        if (!$partes) {
            return 'Invitado';
        }

        $corto = Str::title(Str::lower($partes[0]));

        // Inicial del primer apellido; con 4+ palabras se asume nombre compuesto + dos apellidos
        if (count($partes) >= 2) {
            $apellido = count($partes) >= 4 ? $partes[2] : $partes[1];
            $corto .= ' ' . Str::upper(Str::substr($apellido, 0, 1)) . '.';
        }

        return Str::limit($corto, 24, '…');
    }

    protected function campoNombre(Zona $zona): ?string
    {
        $campos = FormField::where('zona_id', $zona->id)->orderBy('orden')->get();

        return $campos->first(fn ($c) => Str::contains(Str::lower($c->campo . ' ' . $c->etiqueta), 'nombre'))?->campo
            ?? $campos->firstWhere('tipo', 'text')?->campo;
    }

    protected function campoTelefono(Zona $zona): ?string
    {
        $campos = FormField::where('zona_id', $zona->id)->orderBy('orden')->get();

        return $campos->firstWhere('tipo', 'tel')?->campo
            ?? $campos->first(fn ($c) => Str::contains(Str::lower($c->campo . ' ' . $c->etiqueta), ['tel', 'celular', 'whatsapp']))?->campo;
    }
}
