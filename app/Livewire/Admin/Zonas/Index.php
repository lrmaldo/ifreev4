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

    /** Código de vinculación de Telegram recién generado: [zona => nombre, codigo, expira] */
    public ?array $vinculoTelegram = null;

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

    /**
     * Código de un solo uso para ligar un chat de Telegram a la zona (/registrar CODIGO).
     */
    public function generarCodigoTelegram($zonaId): void
    {
        $zona = Zona::findOrFail($zonaId);

        if (!Auth::user()->hasRole('admin') && (int) $zona->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $this->vinculoTelegram = [
            'zona' => $zona->nombre,
            'codigo' => app(\App\Services\TelegramVinculoService::class)->generarCodigo($zona),
            'minutos' => \App\Services\TelegramVinculoService::MINUTOS_VIGENCIA,
            'chats' => $zona->telegramChats()->pluck('nombre')->all(),
        ];
    }

    public function cerrarVinculoTelegram(): void
    {
        $this->vinculoTelegram = null;
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
            // Usamos el ID personalizado si existe, si no, usamos el ID real
            $zonaId = $zona->login_form_id; // Usa el accessor ya definido en el modelo

            // login.html con respaldo: si el servidor no responde, conecta directo (ver la plantilla)
            $html = view('mikrotik.login', [
                'zonaId' => $zonaId,
                'portalUrl' => config('ifree.portal_url'),
                'timeout' => max(2, (int) config('ifree.respaldo_timeout')),
                'trial' => $zona->tipo_autenticacion_mikrotik === 'sin_autenticacion',
            ])->render();

            return response()->streamDownload(fn () => print($html), $fileName, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        // Si es otro archivo (como alogin.html), se devuelve sin modificaciones
        return response()->download($filePath, $fileName);
    }
}
