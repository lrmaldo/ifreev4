<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FormResponse;
use App\Models\Zona;
use Carbon\Carbon;

class FormResponsesList extends Component
{
    use WithPagination;

    public $zona;
    public $searchMac = '';
    public $fechaInicio = '';
    public $fechaFin = '';
    public $perPage = 10;

    protected $queryString = [
        'searchMac' => ['except' => ''],
        'fechaInicio' => ['except' => ''],
        'fechaFin' => ['except' => ''],
        'page' => ['except' => 1]
    ];

    public function mount(Zona $zona)
    {
        $this->zona = $zona;
    }

    public function updatingSearchMac()
    {
        $this->resetPage();
    }

    public function updatingFechaInicio()
    {
        $this->resetPage();
    }

    public function updatingFechaFin()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->searchMac = '';
        $this->fechaInicio = '';
        $this->fechaFin = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = FormResponse::where('zona_id', $this->zona->id)
            ->with(['zona', 'zona.campos', 'zona.campos.opciones'])
            ->orderBy('created_at', 'desc');

        // Filtro por MAC
        if (!empty($this->searchMac)) {
            $query->where('mac_address', 'like', '%' . $this->searchMac . '%');
        }

        // Filtro por fecha de inicio
        if (!empty($this->fechaInicio)) {
            $query->whereDate('created_at', '>=', $this->fechaInicio);
        }

        // Filtro por fecha de fin
        if (!empty($this->fechaFin)) {
            $query->whereDate('created_at', '<=', $this->fechaFin);
        }

        $respuestas = $query->paginate($this->perPage);

        return view('livewire.form-responses-list', [
            'respuestas' => $respuestas
        ]);
    }

    /**
     * Formatear tiempo activo en formato legible
     */
    public function formatearTiempo($segundos)
    {
        if ($segundos < 60) {
            return $segundos . 's';
        } elseif ($segundos < 3600) {
            return floor($segundos / 60) . 'm ' . ($segundos % 60) . 's';
        } else {
            $horas = floor($segundos / 3600);
            $minutos = floor(($segundos % 3600) / 60);
            $segs = $segundos % 60;
            return $horas . 'h ' . $minutos . 'm ' . $segs . 's';
        }
    }

    /**
     * Obtener versión corta del dispositivo
     */
    public function getDispositivoCorto($userAgent)
    {
        if (empty($userAgent)) {
            return 'Desconocido';
        }

        // Detectar móvil
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPhone/', $userAgent)) {
                return 'iPhone';
            } elseif (preg_match('/iPad/', $userAgent)) {
                return 'iPad';
            } elseif (preg_match('/Android/', $userAgent)) {
                return 'Android';
            } else {
                return 'Móvil';
            }
        }

        // Detectar navegador de escritorio
        if (preg_match('/Chrome/', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/Firefox/', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/Safari/', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/Edge/', $userAgent)) {
            return 'Edge';
        }

        return 'Escritorio';
    }
}
