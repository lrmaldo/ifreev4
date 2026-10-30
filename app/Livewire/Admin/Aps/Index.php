<?php

namespace App\Livewire\Admin\Aps;

use App\Models\AccessPoint;
use App\Models\AccessPointLectura;
use App\Models\Zona;
use App\Services\AccessPointsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Access points consultados por SNMP: clientes conectados, tráfico del uplink y
 * cuántos de esos clientes ya pasaron por el portal. Los datos los guarda aps:sondear.
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    /** Horas de historial en la gráfica de cada AP */
    public const HORAS_GRAFICA = 6;

    public bool $mostrarForm = false;

    public ?int $editandoId = null;

    public string $nombre = '';

    public string $host = '';

    public int $puerto = 161;

    public string $community = 'public';

    public ?int $zona_id = null;

    public bool $activo = true;

    public ?int $eliminandoId = null;

    protected function rules(): array
    {
        return [
            'nombre' => 'required|string|max:80',
            'host' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'puerto' => 'required|integer|min:1|max:65535',
            'community' => ['required', 'string', 'max:64', 'regex:/^\S+$/'],
            'zona_id' => 'nullable|integer|exists:zonas,id',
            'activo' => 'boolean',
        ];
    }

    protected $validationAttributes = [
        'host' => 'IP o host',
    ];

    public function nuevo(): void
    {
        $this->resetValidation();
        $this->reset(['editandoId', 'nombre', 'host', 'puerto', 'community', 'zona_id', 'activo']);
        $this->mostrarForm = true;
    }

    public function editar(int $id): void
    {
        $ap = AccessPoint::findOrFail($id);
        $this->resetValidation();
        $this->editandoId = $ap->id;
        $this->nombre = $ap->nombre;
        $this->host = $ap->host;
        $this->puerto = $ap->puerto;
        $this->community = $ap->community;
        $this->zona_id = $ap->zona_id;
        $this->activo = $ap->activo;
        $this->mostrarForm = true;
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        $ap = $this->editandoId
            ? tap(AccessPoint::findOrFail($this->editandoId))->update($datos)
            : AccessPoint::create($datos);

        $this->mostrarForm = false;

        // Se lee de inmediato para confirmar que responde
        if ($ap->activo) {
            app(AccessPointsService::class)->sondear($ap);
        }

        session()->flash($ap->activo && !$ap->en_linea ? 'error' : 'message', $ap->activo && !$ap->en_linea
            ? "\"{$ap->nombre}\" se guardó, pero no responde: {$ap->ultimo_error}"
            : "\"{$ap->nombre}\" guardado.");
    }

    public function probar(int $id): void
    {
        $ap = app(AccessPointsService::class)->sondear(AccessPoint::findOrFail($id));

        session()->flash($ap->en_linea ? 'message' : 'error', $ap->en_linea
            ? "\"{$ap->nombre}\" responde: {$ap->clientes} clientes."
            : "\"{$ap->nombre}\" no responde: {$ap->ultimo_error}");
    }

    public function alternarActivo(int $id): void
    {
        $ap = AccessPoint::findOrFail($id);
        $ap->update(['activo' => !$ap->activo]);
    }

    public function confirmarEliminar(int $id): void
    {
        $this->eliminandoId = $id;
    }

    public function eliminar(): void
    {
        $ap = AccessPoint::find($this->eliminandoId);
        $this->eliminandoId = null;

        if ($ap) {
            $ap->delete();
            session()->flash('message', "\"{$ap->nombre}\" eliminado junto con su historial.");
        }
    }

    /**
     * Clientes del AP en bloques de 5 minutos (el máximo de cada bloque) para la gráfica.
     *
     * @return array<int, array{t: string, clientes: ?int}>
     */
    protected function grafica(AccessPoint $ap): array
    {
        $desde = now()->subHours(self::HORAS_GRAFICA);
        $bloque = fn ($fecha) => intdiv($fecha->getTimestamp(), 300);

        $porBloque = AccessPointLectura::where('access_point_id', $ap->id)
            ->where('created_at', '>=', $desde)
            ->get(['clientes', 'created_at'])
            ->groupBy(fn ($l) => $bloque($l->created_at))
            ->map(fn ($grupo) => $grupo->max('clientes'));

        $puntos = [];
        for ($b = $bloque($desde) + 1; $b <= $bloque(now()); $b++) {
            $puntos[] = ['t' => now()->setTimestamp($b * 300)->format('H:i'), 'clientes' => $porBloque->get($b)];
        }

        return $puntos;
    }

    public function render()
    {
        $servicio = app(AccessPointsService::class);
        $aps = AccessPoint::with('zona:id,nombre')->orderByDesc('activo')->orderBy('nombre')->get();

        $tarjetas = $aps->map(fn (AccessPoint $ap) => [
            'ap' => $ap,
            'responde' => $servicio->respondeReciente($ap),
            'cruce' => $servicio->cruceConPortal($ap),
            'grafica' => $this->grafica($ap),
            'maximoHoy' => (int) AccessPointLectura::where('access_point_id', $ap->id)->where('created_at', '>=', now()->startOfDay())->max('clientes'),
        ]);

        return view('livewire.admin.aps.index', [
            'tarjetas' => $tarjetas,
            'totalClientes' => $tarjetas->where('responde', true)->sum(fn ($t) => (int) $t['ap']->clientes),
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']),
            'eliminando' => $this->eliminandoId ? AccessPoint::find($this->eliminandoId) : null,
        ])->title('Access points');
    }
}
