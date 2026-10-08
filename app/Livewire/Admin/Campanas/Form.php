<?php

namespace App\Livewire\Admin\Campanas;

use App\Models\Campana;
use App\Models\Cliente;
use App\Models\Zona;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Página para crear o editar una campaña (reemplaza el modal del listado).
 */
#[Layout('components.layouts.app')]
class Form extends Component
{
    use WithFileUploads;

    public ?Campana $campana = null;

    public string $titulo = '';
    public string $descripcion = '';
    public string $enlace = '';
    public string $tipo = 'imagen';
    public $archivo = null;
    public bool $visible = true;
    public bool $siempre_visible = false;
    public string $fecha_inicio = '';
    public string $fecha_fin = '';
    public array $dias_visibles = [];
    public int $prioridad = 10;
    public $cliente_id = '';
    public array $zonas_ids = [];
    public string $buscarZona = '';

    public function mount($campanaId = null): void
    {
        $this->fecha_inicio = now()->toDateString();
        $this->fecha_fin = now()->addDays(30)->toDateString();

        if (!$campanaId) {
            return;
        }

        $c = $this->campana = Campana::with('zonas:id')->findOrFail($campanaId);
        $this->titulo = (string) $c->titulo;
        $this->descripcion = (string) $c->descripcion;
        $this->enlace = (string) $c->enlace;
        $this->tipo = $c->tipo;
        $this->visible = (bool) $c->visible;
        $this->siempre_visible = (bool) $c->siempre_visible;
        $this->fecha_inicio = $c->fecha_inicio?->toDateString() ?? $this->fecha_inicio;
        $this->fecha_fin = $c->fecha_fin?->toDateString() ?? $this->fecha_fin;
        $this->dias_visibles = array_map('strval', $c->dias_visibles ?: []);
        $this->prioridad = (int) ($c->prioridad ?? 10);
        $this->cliente_id = $c->cliente_id ?? '';
        $this->zonas_ids = $c->zonas->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    protected function rules(): array
    {
        $archivo = $this->tipo === 'imagen'
            ? 'image|max:2048'
            : 'mimes:mp4,mov,ogg,qt,webm,mpeg,avi|max:102400';

        return [
            'titulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'enlace' => 'nullable|url',
            'tipo' => 'required|in:imagen,video',
            // Al editar se conserva el archivo actual, salvo que cambie el tipo (imagen ↔ video)
            'archivo' => ($this->campana && $this->campana->tipo === $this->tipo ? 'nullable|' : 'required|') . $archivo,
            'visible' => 'boolean',
            'siempre_visible' => 'boolean',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'dias_visibles' => 'array',
            'dias_visibles.*' => 'in:0,1,2,3,4,5,6',
            'prioridad' => 'required|integer|min:1|max:100',
            'cliente_id' => 'nullable|exists:clientes,id',
            'zonas_ids' => 'array',
            'zonas_ids.*' => 'integer|exists:zonas,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'archivo.required' => $this->tipo === 'imagen' ? 'Sube la imagen de la campaña.' : 'Sube el video de la campaña.',
            'archivo.image' => 'El archivo debe ser una imagen (JPG, PNG o WebP).',
            'archivo.max' => $this->tipo === 'imagen' ? 'La imagen pesa más de 2 MB.' : 'El video pesa más de 100 MB.',
            'archivo.mimes' => 'El video debe ser MP4, WebM, MOV u OGG.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'enlace.url' => 'Escribe el enlace completo, con https://',
        ];
    }

    public function updatedTipo(): void
    {
        // Un archivo de imagen no sirve como video y viceversa
        $this->reset('archivo');
        $this->resetValidation('archivo');
    }

    public function updatedSiempreVisible(): void
    {
        if ($this->siempre_visible) {
            $this->dias_visibles = [];
        }
    }

    public function seleccionarZonas(bool $todas): void
    {
        $this->zonas_ids = $todas ? $this->zonasDisponibles()->pluck('id')->map(fn ($id) => (string) $id)->all() : [];
    }

    public function quitarArchivoNuevo(): void
    {
        $this->reset('archivo');
    }

    public function save()
    {
        $this->validate();

        $ruta = $this->campana?->archivo_path;
        if ($this->archivo) {
            $carpeta = $this->tipo === 'imagen' ? 'campanas/imagenes' : 'campanas/videos';
            $nombre = time() . '_' . Str::random(10) . '.' . strtolower($this->archivo->getClientOriginalExtension());
            $nueva = $this->archivo->storeAs($carpeta, $nombre, 'public');

            if (!$nueva) {
                $this->addError('archivo', 'No se pudo guardar el archivo. Revisa los permisos de storage/app/public.');
                return null;
            }
            if ($ruta && $ruta !== $nueva) {
                Storage::disk('public')->delete($ruta);
            }
            $ruta = $nueva;
        }

        $datos = [
            'titulo' => trim($this->titulo) ?: null,
            'descripcion' => trim($this->descripcion) ?: null,
            'enlace' => trim($this->enlace) ?: null,
            'tipo' => $this->tipo,
            'archivo_path' => $ruta,
            'visible' => $this->visible,
            'siempre_visible' => $this->siempre_visible,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            // Los días se guardan como texto ("1"), que es lo que busca scopeActivas
            'dias_visibles' => $this->siempre_visible || empty($this->dias_visibles) ? null : array_values(array_map('strval', $this->dias_visibles)),
            'prioridad' => $this->prioridad,
            'cliente_id' => $this->cliente_id ?: null,
        ];

        if ($this->campana) {
            $this->campana->update($datos);
            $campana = $this->campana;
        } else {
            $campana = Campana::create($datos);
        }

        // Un usuario que no es admin solo puede asignar sus propias zonas
        $permitidas = $this->zonasDisponibles()->pluck('id')->all();
        $campana->zonas()->sync(array_values(array_intersect(array_map('intval', $this->zonas_ids), $permitidas)));

        $nombre = $campana->titulo ?: 'sin título';
        session()->flash('message', $this->campana ? "Campaña \"{$nombre}\" actualizada." : "Campaña \"{$nombre}\" creada.");

        return $this->redirectRoute('admin.campanas.index', navigate: true);
    }

    protected function zonasDisponibles()
    {
        $user = Auth::user();

        return Zona::query()
            ->when(!$user->hasRole('admin'), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'id_personalizado']);
    }

    public function render()
    {
        $zonas = $this->zonasDisponibles();
        $filtradas = $this->buscarZona === ''
            ? $zonas
            : $zonas->filter(fn ($z) => Str::contains(Str::lower($z->nombre . ' ' . $z->id_personalizado), Str::lower($this->buscarZona)));

        return view('livewire.admin.campanas.form', [
            'zonas' => $filtradas,
            'totalZonas' => $zonas->count(),
            'clientes' => Cliente::orderBy('nombre_comercial')->get(),
        ])->title($this->campana ? 'Editar campaña' : 'Nueva campaña');
    }
}
