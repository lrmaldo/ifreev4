<?php

namespace App\Livewire\Admin\Zonas;

use App\Models\Zona;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Página para crear o editar una zona (reemplaza el modal del listado).
 */
#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?Zona $zonaModelo = null;

    public array $zona = [];

    public function mount($zonaId = null): void
    {
        if ($zonaId) {
            $this->zonaModelo = Zona::findOrFail($zonaId);

            if (!Auth::user()->hasRole('admin') && (int) $this->zonaModelo->user_id !== (int) Auth::id()) {
                abort(403);
            }
        }

        $z = $this->zonaModelo;
        $this->zona = [
            'nombre' => $z->nombre ?? '',
            'id_personalizado' => $z->id_personalizado ?? '',
            'segundos' => $z->segundos ?? 15,
            'tipo_registro' => $z->tipo_registro ?? 'formulario',
            'login_sin_registro' => (bool) ($z->login_sin_registro ?? false),
            'tipo_autenticacion_mikrotik' => $z->tipo_autenticacion_mikrotik ?? 'usuario_password',
            'script_head' => $z->script_head ?? '',
            'script_body' => $z->script_body ?? '',
            'telegram_resumen_minutos' => $z->telegram_resumen_minutos ?? '',
            'portal_tema' => $z->portal_tema ?? 'clasico',
            'portal_mensaje' => $z->portal_mensaje ?? '',
            'portal_marca_evento' => (bool) ($z->portal_marca_evento ?? false),
            'portal_rifa' => (bool) ($z->portal_rifa ?? false),
            'portal_socios' => (bool) ($z->portal_socios ?? false),
        ];
    }

    protected function rules(): array
    {
        $unico = 'unique:zonas,id_personalizado' . ($this->zonaModelo ? ',' . $this->zonaModelo->id : '');

        return [
            'zona.nombre' => 'required|string|max:255',
            'zona.id_personalizado' => "nullable|string|max:50|{$unico}|regex:/^[a-zA-Z0-9_-]+$/|not_in:admin,login,register,dashboard",
            'zona.segundos' => 'required|integer|min:5',
            'zona.tipo_registro' => 'required|string|in:formulario,redes,sin_registro',
            'zona.login_sin_registro' => 'boolean',
            'zona.tipo_autenticacion_mikrotik' => 'required|string|in:pin,usuario_password,sin_autenticacion',
            'zona.script_head' => 'nullable|string',
            'zona.script_body' => 'nullable|string',
            'zona.telegram_resumen_minutos' => 'nullable|integer|in:5,10,15,30,60',
            'zona.portal_tema' => 'required|in:clasico,evento',
            'zona.portal_mensaje' => 'nullable|string|max:160',
            'zona.portal_marca_evento' => 'boolean',
            'zona.portal_rifa' => 'boolean',
            'zona.portal_socios' => 'boolean',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'zona.nombre' => 'nombre',
            'zona.id_personalizado' => 'ID personalizado',
            'zona.segundos' => 'segundos',
            'zona.portal_mensaje' => 'mensaje de bienvenida',
        ];
    }

    public function save()
    {
        $this->validate();

        $datos = $this->zona;
        $datos['telegram_resumen_minutos'] = $datos['telegram_resumen_minutos'] ?: null;
        $datos['portal_mensaje'] = trim($datos['portal_mensaje'] ?? '') ?: null;
        $datos['id_personalizado'] = trim($datos['id_personalizado'] ?? '') ?: null;
        // Los logos del evento solo aplican al tema evento
        $datos['portal_marca_evento'] = $datos['portal_tema'] === 'evento' && $datos['portal_marca_evento'];

        if ($this->zonaModelo) {
            $this->zonaModelo->update($datos);
            session()->flash('message', "Zona \"{$this->zonaModelo->nombre}\" actualizada.");
        } else {
            $nueva = new Zona($datos);
            $nueva->user_id = Auth::id();
            $nueva->save();
            session()->flash('message', "Zona \"{$nueva->nombre}\" creada. Agrega sus campos de formulario y asígnale campañas.");
        }

        return $this->redirectRoute('admin.zonas.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.zonas.form', [
            'tipoRegistroOptions' => (new Zona)->getTipoRegistroOptions(),
            'tipoAutenticacionMikrotikOptions' => (new Zona)->getTipoAutenticacionMikrotikOptions(),
        ])->title($this->zonaModelo ? "Editar zona · {$this->zonaModelo->nombre}" : 'Nueva zona');
    }
}
