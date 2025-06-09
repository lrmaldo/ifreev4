<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Zona;
use App\Traits\RenderizaFormFields;
use App\Models\FormResponse;

class FormularioDinamico extends Component
{
    use RenderizaFormFields;

    public $zonaId;
    public $formulario = [];
    public $macAddress = '';
    public $tiempoActivo = 0;
    public $dispositivo = '';
    public $navegador = '';

    public function mount($zonaId = null)
    {
        $this->zonaId = $zonaId;
        $this->macAddress = request()->get('mac', '');
        $this->dispositivo = request()->header('User-Agent', '');

        // Detectar navegador
        $this->navegador = $this->detectarNavegador($this->dispositivo);
    }
      public function render()
    {
        $zona = $this->zonaId ? Zona::findOrFail($this->zonaId) : null;

        return view('livewire.formulario-dinamico', [
            'zona' => $zona,
            'campos' => $zona ? $zona->campos()->orderBy('orden')->get() : collect(),
        ]);
    }

    public function guardar()
    {
        // Si es necesario, puedes validar los campos obligatorios
        $zona = Zona::findOrFail($this->zonaId);
        $campos = $zona->campos()->where('obligatorio', true)->get();

        $reglas = [];

        foreach ($campos as $campo) {
            if ($campo->tipo != 'checkbox') {
                $reglas["formulario.{$campo->campo}"] = 'required';
            }
        }

        $this->validate($reglas);

        // Preparar respuestas para guardar
        $respuestas = [];
        foreach ($zona->campos as $campo) {
            if (isset($this->formulario[$campo->campo])) {
                $respuestas[$campo->id] = $this->formulario[$campo->campo];
            }
        }

        // Guardar la respuesta
        try {
            FormResponse::create([
                'zona_id' => $this->zonaId,
                'mac_address' => $this->macAddress,
                'dispositivo' => $this->dispositivo,
                'navegador' => $this->navegador,
                'tiempo_activo' => $this->tiempoActivo,
                'formulario_completado' => true,
                'respuestas' => $respuestas
            ]);

            session()->flash('message', 'Formulario guardado correctamente.');

            // Limpiar formulario
            $this->formulario = [];

        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar el formulario: ' . $e->getMessage());
        }
    }

    /**
     * Detectar navegador desde User-Agent
     */
    private function detectarNavegador($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false) {
            return 'Opera';
        }

        return 'Desconocido';
    }

    /**
     * Actualizar tiempo activo (llamado desde JavaScript)
     */
    public function actualizarTiempo($segundos)
    {
        $this->tiempoActivo = $segundos;
    }
}
