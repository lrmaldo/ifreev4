<?php

namespace App\Livewire\Admin\Users;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Página para crear o editar un usuario (reemplaza el modal del listado).
 */
#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?User $usuario = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $cliente_id = '';

    /** Nombres de rol seleccionados */
    public array $roles = [];

    public function mount($userId = null): void
    {
        if ($userId) {
            $this->usuario = User::findOrFail($userId);
            $this->name = $this->usuario->name;
            $this->email = $this->usuario->email;
            $this->cliente_id = (string) ($this->usuario->cliente_id ?? '');
            $this->roles = $this->usuario->getRoleNames()->all();
        } else {
            $this->roles = Role::where('name', 'cliente')->exists() ? ['cliente'] : [];
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->usuario?->id)],
            'password' => $this->usuario ? 'nullable|string|min:8' : 'required|string|min:8',
            'cliente_id' => 'nullable|exists:clientes,id',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ];
    }

    protected function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario con este correo.',
            'password.required' => 'Escribe una contraseña o genera una.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    public function generarPassword(): void
    {
        $this->password = Str::password(12, symbols: false);
    }

    public function save()
    {
        $this->validate();

        // Que el admin no se quite a sí mismo el acceso al panel de administración
        if ($this->usuario?->id === Auth::id() && $this->usuario->hasRole('admin') && !in_array('admin', $this->roles, true)) {
            $this->addError('roles', 'No puedes quitarte el rol de administrador a ti mismo.');
            return null;
        }

        $user = $this->usuario ?? new User();
        $user->name = $this->name;
        $user->email = $this->email;
        // cliente_id no está en $fillable: antes se perdía en silencio al guardar
        $user->cliente_id = $this->cliente_id !== '' ? (int) $this->cliente_id : null;

        if ($this->password !== '') {
            $user->password = $this->password; // el cast "hashed" lo cifra
        }

        $user->save();
        $user->syncRoles($this->roles);

        session()->flash('message', $this->usuario ? "Usuario \"{$user->name}\" actualizado." : "Usuario \"{$user->name}\" creado.");

        return $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.form', [
            'rolesDisponibles' => Role::orderBy('name')->get(),
            'clientes' => Cliente::orderBy('nombre_comercial')->get(),
            'zonas' => $this->usuario?->zonas()->orderBy('nombre')->get(['id', 'nombre']) ?? collect(),
        ])->title($this->usuario ? 'Editar usuario' : 'Nuevo usuario');
    }
}
