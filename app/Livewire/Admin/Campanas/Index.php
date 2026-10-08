<?php

namespace App\Livewire\Admin\Campanas;

use App\Models\Campana;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de campañas del admin. Crear/editar vive en Admin\Campanas\Form (página propia).
 */
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'tipo', except: '')]
    public string $filtroTipo = '';

    #[Url(as: 'estado', except: '')]
    public string $filtroEstado = '';

    #[Url(as: 'cliente', except: '')]
    public string $filtroCliente = '';

    public $confirmandoEliminar = null;

    public function updating($propiedad): void
    {
        if (in_array($propiedad, ['search', 'filtroTipo', 'filtroEstado', 'filtroCliente'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'filtroTipo', 'filtroEstado', 'filtroCliente']);
        $this->resetPage();
    }

    public function toggleVisibility($id): void
    {
        $campana = Campana::findOrFail($id);
        $campana->update(['visible' => !$campana->visible]);
    }

    public function confirmarEliminar($id): void
    {
        $this->confirmandoEliminar = $id;
    }

    public function eliminar(): void
    {
        $campana = Campana::findOrFail($this->confirmandoEliminar);

        if ($campana->archivo_path) {
            Storage::disk('public')->delete($campana->archivo_path);
        }
        $nombre = $campana->titulo ?: 'sin título';
        $campana->delete();

        $this->confirmandoEliminar = null;
        session()->flash('message', "Campaña \"{$nombre}\" eliminada.");
    }

    /**
     * Revisa y repara las carpetas donde se guardan los archivos de campañas.
     */
    public function ejecutarDiagnostico(): void
    {
        $problemas = [];

        foreach (['campanas/imagenes', 'campanas/videos'] as $carpeta) {
            if (!Storage::disk('public')->exists($carpeta)) {
                Storage::disk('public')->makeDirectory($carpeta);
            }
            $ruta = storage_path('app/public/' . $carpeta);
            if (!is_writable($ruta)) {
                $problemas[] = "sin permiso de escritura en {$ruta}";
            }
        }

        if (!file_exists(public_path('storage'))) {
            $problemas[] = 'falta el enlace public/storage (corre php artisan storage:link)';
        }

        Log::info('Diagnóstico de archivos de campañas', [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'problemas' => $problemas,
        ]);

        $problemas
            ? session()->flash('error', 'Diagnóstico: ' . implode('; ', $problemas) . '.')
            : session()->flash('message', 'Diagnóstico: carpetas y enlace de archivos en orden (subida máxima ' . ini_get('upload_max_filesize') . ').');
    }

    public function render()
    {
        $campanas = Campana::query()
            ->with('cliente')
            ->withCount('zonas')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('titulo', 'like', "%{$this->search}%")
                ->orWhere('descripcion', 'like', "%{$this->search}%")))
            ->when($this->filtroTipo, fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroCliente === 'global', fn ($q) => $q->whereNull('cliente_id'))
            ->when($this->filtroCliente && $this->filtroCliente !== 'global', fn ($q) => $q->where('cliente_id', $this->filtroCliente))
            ->when($this->filtroEstado === 'activas', fn ($q) => $q->activas())
            ->when($this->filtroEstado === 'programadas', fn ($q) => $q->programadas())
            ->when($this->filtroEstado === 'vencidas', fn ($q) => $q->vencidas())
            ->when($this->filtroEstado === 'ocultas', fn ($q) => $q->where('visible', false))
            ->latest()
            ->paginate(12);

        return view('livewire.admin.campanas.index', [
            'campanas' => $campanas,
            'clientes' => Cliente::orderBy('nombre_comercial')->get(),
            'hayFiltros' => $this->search || $this->filtroTipo || $this->filtroEstado || $this->filtroCliente,
        ]);
    }
}
