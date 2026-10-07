<?php

namespace App\Livewire\Admin\Zonas;

use App\Models\Zona;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de zonas del admin. Crear/editar vive en Admin\Zonas\Form (página propia)
 * y los campos del formulario en AdminFormFields.
 */
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $confirmingZonaDeletion = false;
    public $showInstructionsModal = false;
    public $activeZonaForInstructions = null;

    public function render()
    {
        $user = Auth::user();

        $zonas = Zona::query()
            ->with('user:id,name')
            ->withCount(['campos', 'campanas'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'like', '%' . $this->search . '%')
                ->orWhere('id_personalizado', 'like', '%' . $this->search . '%')))
            ->when(!$user->hasRole('admin'), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.zonas.index', ['zonas' => $zonas]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    /**
     * Crea (o reemplaza) el link secreto de la pantalla en vivo de la zona.
     */
    public function generarLinkPantalla($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        if (!Auth::user()->hasRole('admin') && (int) $zona->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $zona->forceFill(['pantalla_token' => \Illuminate\Support\Str::random(48)])->save();

        session()->flash('message', 'Link de la pantalla en vivo: ' . route('evento.pantalla', ['token' => $zona->pantalla_token]));
    }

    public function confirmZonaDeletion($zonaId)
    {
        $this->confirmingZonaDeletion = $zonaId;
    }

    public function deleteZona()
    {
        $zona = Zona::findOrFail($this->confirmingZonaDeletion);

        if (Auth::user()->hasRole('admin') || (int) $zona->user_id === (int) Auth::id()) {
            $zona->campos()->delete();
            $zona->delete();
            session()->flash('message', "Zona \"{$zona->nombre}\" eliminada.");
        } else {
            session()->flash('error', 'No tienes permisos para eliminar esta zona.');
        }

        $this->confirmingZonaDeletion = false;
    }

    public function openInstructionsModal($zonaId)
    {
        $this->activeZonaForInstructions = Zona::findOrFail($zonaId);
        $this->showInstructionsModal = true;
    }

    public function closeInstructionsModal()
    {
        $this->showInstructionsModal = false;
        $this->activeZonaForInstructions = null;
    }

    public function downloadMikrotikFile($zonaId, $fileType)
    {
        $zona = Zona::findOrFail($zonaId);

        // Verificar permisos: solo admins o el propietario de la zona pueden descargar
        if (!auth()->user()->hasRole('admin') && $zona->user_id !== auth()->id()) {
            session()->flash('error', 'No tienes permisos para acceder a esta zona.');
            return redirect()->back();
        }

        // Solo los archivos de plantilla conocidos
        if (!in_array($fileType, ['login', 'alogin'], true)) {
            abort(404);
        }

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
            <form name="redirect" action="https://v3.i-free.com.mx/login_formulario/{$zonaId}" method="post">
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
