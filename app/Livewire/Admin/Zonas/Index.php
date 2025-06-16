<?php

namespace App\Livewire\Admin\Zonas;

use App\Models\FormField;
use App\Models\Zona;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $showModal = false;
    public $showFieldModal = false;
    public $showInstructionsModal = false;
    public $isEditing = false;
    public $isEditingField = false;
    public $activeZona = null;
    public $activeField = null;
    public $activeZonaForInstructions = null;
    public $confirmingZonaDeletion = false;
    public $confirmingFieldDeletion = false;
    public $search = '';
    public $perPage = 10;

    // Propiedades para zona
    public $zona = [
        'nombre' => '',
        'id_personalizado' => '', // ID personalizado para login.html
        'segundos' => 15,
        'tipo_registro' => 'formulario',
        'login_sin_registro' => false,
        'tipo_autenticacion_mikrotik' => 'usuario_password',
        'script_head' => '',
        'script_body' => ''
    ];

    // Propiedades para campo de formulario
    public $formField = [
        'zona_id' => null,
        'campo' => '',
        'etiqueta' => '',
        'tipo' => 'text',
        'obligatorio' => true,
        'orden' => 0
    ];

    protected $rules = [
        'zona.nombre' => 'required|string|max:255',
        'zona.id_personalizado' => 'nullable|string|max:50|unique:zonas,id_personalizado|regex:/^[a-zA-Z0-9_-]+$/|not_in:admin,login,register,dashboard',
        'zona.segundos' => 'required|integer|min:5',
        'zona.tipo_registro' => 'required|string|in:formulario,redes,sin_registro',
        'zona.login_sin_registro' => 'boolean',
        'zona.tipo_autenticacion_mikrotik' => 'required|string|in:pin,usuario_password,sin_autenticacion',
        'zona.script_head' => 'nullable|string',
        'zona.script_body' => 'nullable|string',
    ];

    protected $formFieldRules = [
        'formField.campo' => 'required|string|max:255',
        'formField.etiqueta' => 'required|string|max:255',
        'formField.tipo' => 'required|string|in:text,email,tel,number,select,radio,checkbox',
        'formField.obligatorio' => 'boolean',
        'formField.orden' => 'integer|min:0',
    ];

    protected $listeners = ['refresh' => '$refresh', 'openZonaModal' => 'openModal'];

    // Método adicional para debugging y comunicación directa
    public function openNewZonaModal()
    {
        $this->openModal();
    }

    public function render()
    {
        $user = Auth::user();
        $query = Zona::query()
            ->when($this->search, function ($query) {
                return $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->when(!$user->hasRole('admin'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->latest();

        $zonas = $query->paginate($this->perPage);

        return view('livewire.admin.zonas.index', [
            'zonas' => $zonas,
            'tipoRegistroOptions' => (new Zona)->getTipoRegistroOptions(),
            'tipoAutenticacionMikrotikOptions' => (new Zona)->getTipoAutenticacionMikrotikOptions(),
            'tipoFieldOptions' => (new FormField)->getTipoOptions(),
        ]);
    }

    public function openModal($isEditing = false, $zonaId = null)
    {
        $this->resetValidation();
        $this->isEditing = $isEditing;

        if ($isEditing && $zonaId) {
            $zona = Zona::findOrFail($zonaId);
            $this->activeZona = $zona;
            $this->zona = $zona->toArray();
            $this->zona['login_sin_registro'] = (bool) $zona->login_sin_registro;
        } else {
            $this->activeZona = null;
            $this->zona = [
                'nombre' => '',
                'id_personalizado' => '',
                'segundos' => 15,
                'tipo_registro' => 'formulario',
                'login_sin_registro' => false,
                'tipo_autenticacion_mikrotik' => 'usuario_password',
                'script_head' => '',
                'script_body' => ''
            ];
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function saveZona()
    {
        // Validación personalizada para id_personalizado
        $rules = $this->rules;
        if ($this->isEditing && $this->activeZona) {
            $rules['zona.id_personalizado'] = 'nullable|string|max:50|unique:zonas,id_personalizado,' . $this->activeZona->id . '|regex:/^[a-zA-Z0-9_-]+$/|not_in:admin,login,register,dashboard';
        }
        $this->validate($rules);

        // Si el id_personalizado está vacío, establecerlo a NULL para evitar problemas de unicidad
        if (empty(trim($this->zona['id_personalizado']))) {
            $this->zona['id_personalizado'] = null;
        }

        if ($this->isEditing && $this->activeZona) {
            $this->activeZona->update($this->zona);
            session()->flash('message', 'Zona actualizada correctamente.');
        } else {
            $zona = new Zona($this->zona);
            $zona->user_id = Auth::id();
            $zona->save();
            session()->flash('message', 'Zona creada correctamente.');
        }

        $this->closeModal();
        $this->reset(['zona']);
    }

    public function confirmZonaDeletion($zonaId)
    {
        $this->confirmingZonaDeletion = $zonaId;
    }

    public function deleteZona()
    {
        $zona = Zona::findOrFail($this->confirmingZonaDeletion);

        // Solo el admin puede eliminar zonas que no son suyas
        if (Auth::user()->hasRole('admin') || $zona->user_id === Auth::id()) {
            // Eliminar los campos de formulario asociados
            $zona->campos()->delete();
            $zona->delete();
            session()->flash('message', 'Zona eliminada correctamente.');
        } else {
            session()->flash('error', 'No tienes permisos para eliminar esta zona.');
        }

        $this->confirmingZonaDeletion = false;
    }

    // MÉTODOS PARA FORM FIELDS

    public function openFieldModal($zonaId, $isEditing = false, $fieldId = null)
    {
        // Verificar que la zona no sea de tipo 'sin_registro'
        $zona = Zona::findOrFail($zonaId);
        if ($zona->tipo_registro === 'sin_registro') {
            session()->flash('error', 'No se pueden agregar campos a zonas con tipo de registro "Sin registro".');
            return;
        }

        $this->resetValidation();
        $this->isEditingField = $isEditing;
        $this->formField['zona_id'] = $zonaId;

        if ($isEditing && $fieldId) {
            $field = FormField::findOrFail($fieldId);
            $this->activeField = $field;
            $this->formField = $field->toArray();
            $this->formField['obligatorio'] = (bool) $field->obligatorio;
        } else {
            $this->activeField = null;
            $this->formField = [
                'zona_id' => $zonaId,
                'campo' => '',
                'etiqueta' => '',
                'tipo' => 'text',
                'obligatorio' => true,
                'orden' => $this->getNextFieldOrder($zonaId)
            ];
        }

        $this->showFieldModal = true;
    }

    public function closeFieldModal()
    {
        $this->showFieldModal = false;
    }

    public function getNextFieldOrder($zonaId)
    {
        $maxOrder = FormField::where('zona_id', $zonaId)->max('orden');
        return is_null($maxOrder) ? 0 : $maxOrder + 1;
    }

    public function saveField()
    {
        $this->validate([
            'formField.campo' => 'required|string|max:255',
            'formField.etiqueta' => 'required|string|max:255',
            'formField.tipo' => 'required|string|in:text,email,tel,number,select,radio,checkbox',
            'formField.obligatorio' => 'boolean',
            'formField.orden' => 'integer|min:0',
        ]);

        if ($this->isEditingField && $this->activeField) {
            $this->activeField->update($this->formField);
            session()->flash('message', 'Campo actualizado correctamente.');
        } else {
            FormField::create($this->formField);
            session()->flash('message', 'Campo creado correctamente.');
        }

        $this->closeFieldModal();
        $this->reset(['formField']);
    }

    public function confirmFieldDeletion($fieldId)
    {
        $this->confirmingFieldDeletion = $fieldId;
    }

    public function deleteField()
    {
        $field = FormField::findOrFail($this->confirmingFieldDeletion);
        $field->delete();
        session()->flash('message', 'Campo eliminado correctamente.');
        $this->confirmingFieldDeletion = false;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openInstructionsModal($zonaId)
    {
        try {
            $zona = Zona::findOrFail($zonaId);
            $this->activeZonaForInstructions = $zona;
            $this->showInstructionsModal = true;

            // Emitir evento para JavaScript
            $this->dispatch('showInstructionsModal');

            // Debug para confirmar que el método se está llamando
            session()->flash('message', "Modal de instrucciones abierto para zona: {$zona->nombre}");
        } catch (\Exception $e) {
            session()->flash('error', "Error al abrir instrucciones: {$e->getMessage()}");
        }
    }

    public function closeInstructionsModal()
    {
        $this->showInstructionsModal = false;
        $this->activeZonaForInstructions = null;
    }

    public function downloadMikrotikFile($zonaId, $fileType)
    {
        $zona = Zona::findOrFail($zonaId);
        $fileName = $fileType . '.html';
        $filePath = public_path('templates/' . $fileName);

        if (!file_exists($filePath)) {
            session()->flash('error', "El archivo $fileName no existe.");
            return;
        }

        // Si es el archivo login.html, personalizar con el ID de la zona (real o personalizado)
        if ($fileType === 'login') {
            $content = file_get_contents($filePath);

            // Usamos el ID personalizado si existe, si no, usamos el ID real
            $zonaId = $zona->login_form_id; // Usa el accessor ya definido en el modelo

            // Para el archivo de referencia, necesitamos crear un formulario que apunte a nuestra URL
            $html = <<<HTML
<html>
    <head> <title>Redirecting...</title></head>
        <body>
            $(if chap-id)
                <noscript>
                <center><b>JavaScript required. Enable JavaScript to continue.</b></center>
                </noscript>
            $(endif)
        <center>Si no se redirecciona en unos segundos haga clic en 'continue'<br>
            <form name="redirect" action="https://i-free.com.mx/login_formulario/{$zonaId}" method="post">
                <input type="hidden" name="mac" value="$(mac)">
                <input type="hidden" name="ip" value="$(ip)">
                <input type="hidden" name="username" value="$(username)">
                <input type="hidden" name="link-login" value="$(link-login)">
                <input type="hidden" name="link-orig" value="$(link-orig)">
                <input type="hidden" name="error" value="$(error)">
                <input type="hidden" name="chap-id" value="$(chap-id)">
                <input type="hidden" name="chap-challenge" value="$(chap-challenge)">
                <input type="hidden" name="link-login-only" value="$(link-login-only)">
                <input type="hidden" name="link-orig-esc" value="$(link-orig-esc)">
                <input type="hidden" name="mac-esc" value="$(mac-esc)">
                <input type="submit" value="continue">
            </form>
        <script language="JavaScript">
        <!--
            document.redirect.submit();
            //-->
        </script></center>
        </body>
    </html>
HTML;

            // Guardar el contenido en un archivo temporal y devolverlo como descarga
            $tempFilePath = sys_get_temp_dir() . '/' . $fileName;
            file_put_contents($tempFilePath, $html);

            return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
        }

        // Si es otro archivo (como alogin.html), se devuelve sin modificaciones
        return response()->download($filePath, $fileName);
    }
}
