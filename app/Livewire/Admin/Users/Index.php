<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

/**
 * Listado de usuarios. Crear y editar se hace en su propia página (Admin\Users\Form).
 */
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'rol', except: '')]
    public string $filtroRol = '';

    public ?int $eliminandoId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroRol(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'filtroRol']);
        $this->resetPage();
    }

    public function confirmarEliminar(int $userId): void
    {
        $this->eliminandoId = $userId;
    }

    public function eliminar(): void
    {
        $user = User::withCount('zonas')->find($this->eliminandoId);
        $this->eliminandoId = null;

        if (!$user) {
            return;
        }

        if ($motivo = $this->motivoNoEliminar($user)) {
            session()->flash('error', $motivo);
            return;
        }

        $nombre = $user->name;
        $user->delete();
        session()->flash('message', "Usuario \"{$nombre}\" eliminado.");
    }

    /**
     * Las zonas tienen ON DELETE CASCADE hacia users: borrar al dueño borraría sus zonas,
     * campos, respuestas y métricas. Por eso no se permite mientras tenga zonas.
     */
    public function motivoNoEliminar(User $user): ?string
    {
        if ($user->id === Auth::id()) {
            return 'No puedes eliminar tu propia cuenta desde aquí.';
        }

        if ($user->zonas_count > 0) {
            return "{$user->name} tiene {$user->zonas_count} " . ($user->zonas_count === 1 ? 'zona' : 'zonas')
                . ' a su nombre. Asígnalas a otro usuario antes de eliminarlo.';
        }

        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return 'Es el único administrador; no se puede eliminar.';
        }

        return null;
    }

    public function render()
    {
        $roles = Role::withCount('users')->orderBy('name')->get();

        // Un rol inexistente en la URL haría fallar el scope role()
        if ($this->filtroRol !== '' && $this->filtroRol !== 'sin_rol' && !$roles->contains('name', $this->filtroRol)) {
            $this->filtroRol = '';
        }

        $usuarios = User::query()
            ->with(['roles', 'cliente'])
            ->withCount('zonas')
            ->when($this->search !== '', function ($q) {
                $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"));
            })
            ->when($this->filtroRol === 'sin_rol', fn ($q) => $q->doesntHave('roles'))
            ->when($this->filtroRol !== '' && $this->filtroRol !== 'sin_rol', fn ($q) => $q->role($this->filtroRol))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.users.index', [
            'usuarios' => $usuarios,
            'roles' => $roles,
            'total' => User::count(),
            'sinRol' => User::doesntHave('roles')->count(),
            'hayFiltros' => $this->search !== '' || $this->filtroRol !== '',
            'eliminando' => $this->eliminandoId ? User::withCount('zonas')->find($this->eliminandoId) : null,
        ]);
    }
}
