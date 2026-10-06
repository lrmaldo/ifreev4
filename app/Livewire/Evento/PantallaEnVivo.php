<?php

namespace App\Livewire\Evento;

use App\Models\RifaGanador;
use App\Models\Zona;
use App\Services\EventoEnVivoService;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pantalla en vivo de una zona para la TV del stand (aforo, registros, anuncios y rifa).
 * Se abre con un link secreto (zonas.pantalla_token), sin iniciar sesión.
 */
class PantallaEnVivo extends Component
{
    #[Locked]
    public string $token;

    #[Locked]
    public int $zonaId;

    public array $stats = [];

    public function mount(string $token): void
    {
        $zona = Zona::where('pantalla_token', $token)->firstOrFail();

        $this->token = $token;
        $this->zonaId = $zona->id;
        $this->actualizar();
    }

    public function actualizar(): void
    {
        $this->stats = app(EventoEnVivoService::class)->estadisticas($this->zona());
    }

    public function contarParticipantes(): int
    {
        return app(EventoEnVivoService::class)->participantes($this->zona())->count();
    }

    public function sortear(?string $premio = null): array
    {
        $resultado = app(EventoEnVivoService::class)->sortear($this->zona(), $premio);
        $ganador = $resultado['ganador'];

        return [
            'ganador' => $ganador ? [
                'nombre' => $ganador->nombre,
                'telefono_final' => $ganador->telefono_final,
                'premio' => $ganador->premio,
            ] : null,
            'nombres' => $resultado['nombres'],
            'participantes' => $resultado['participantes'],
        ];
    }

    /**
     * Si el link se regeneró, las pantallas abiertas con el anterior dejan de funcionar.
     */
    protected function zona(): Zona
    {
        return Zona::where('id', $this->zonaId)
            ->where('pantalla_token', $this->token)
            ->firstOr(fn () => abort(403, 'El link de esta pantalla ya no es válido'));
    }

    public function render()
    {
        $zona = $this->zona();

        return view('livewire.evento.pantalla-en-vivo', [
            'zona' => $zona,
            'ganadores' => RifaGanador::where('zona_id', $zona->id)->latest()->limit(5)->get(),
            // El logo de la asociación organizadora; "EXPO MX-ISP 2026" se dibuja con CSS
            'logoWispmx' => file_exists(public_path('img/evento/logo-wispmx.webp')) ? asset('img/evento/logo-wispmx.webp') : null,
            'socios' => $this->socios(),
        ]);
    }

    /**
     * Logos para el carrusel de socios: public/img/evento/socios/socios.json
     * con [{"nombre": "...", "archivo": "..."}]. Sin ese archivo no se muestra el carrusel.
     */
    protected function socios(): array
    {
        $manifiesto = public_path('img/evento/socios/socios.json');
        $lista = file_exists($manifiesto) ? json_decode(file_get_contents($manifiesto), true) : null;

        return collect(is_array($lista) ? $lista : [])
            ->filter(fn ($s) => !empty($s['archivo']) && file_exists(public_path('img/evento/socios/' . basename($s['archivo']))))
            ->map(fn ($s) => [
                'nombre' => $s['nombre'] ?? pathinfo($s['archivo'], PATHINFO_FILENAME),
                'url' => asset('img/evento/socios/' . basename($s['archivo'])),
            ])
            ->values()
            ->all();
    }
}
