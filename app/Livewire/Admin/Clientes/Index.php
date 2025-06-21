<?php

namespace App\Livewire\Admin\Clientes;

use App\Models\Cliente;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    // Propiedades del modal
    public $showModal = false;
    public $clienteId = null;
    public $razonSocial = '';
    public $rfc = '';
    public $telefono = '';
    public $correo = '';
    public $direccion = '';
    public $nombreComercial = '';

    protected $listeners = ['openModal' => 'openModal', 'closeModal'];

    protected function getListeners()
    {
        return [
            'openModal' => 'openModal',
            'closeModal' => 'closeModal',
        ];
    }

    protected $rules = [
        'razonSocial' => 'required|min:3',
        'rfc' => 'nullable|string|max:13',
        'telefono' => 'nullable|string|max:15',
        'correo' => 'nullable|email',
        'direccion' => 'nullable|string',
        'nombreComercial' => 'nullable|string|max:255',
    ];

    public function openModal($clienteId = null)
    {
        $this->resetFields();
        $this->clienteId = $clienteId;

        if ($clienteId) {
            $cliente = Cliente::find($clienteId);
            if ($cliente) {
                $this->razonSocial = $cliente->{'razon social'};
                $this->rfc = $cliente->rfc;
                $this->telefono = $cliente->telefono;
                $this->correo = $cliente->correo;
                $this->direccion = $cliente->direccion;
                $this->nombreComercial = $cliente->nombre_comercial;
            }
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFields();
        $this->resetValidation();
    }

    public function resetFields()
    {
        $this->clienteId = null;
        $this->razonSocial = '';
        $this->rfc = '';
        $this->telefono = '';
        $this->correo = '';
        $this->direccion = '';
        $this->nombreComercial = '';
    }

    public function save()
    {
        $this->validate();

        $data = [
            'razon social' => $this->razonSocial,
            'rfc' => $this->rfc,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'direccion' => $this->direccion,
            'nombre_comercial' => $this->nombreComercial,
        ];

        if ($this->clienteId) {
            Cliente::find($this->clienteId)->update($data);
            session()->flash('message', 'Cliente actualizado exitosamente.');
        } else {
            Cliente::create($data);
            session()->flash('message', 'Cliente creado exitosamente.');
        }

        $this->closeModal();
    }

    public function deleteCliente($clienteId)
    {
        $cliente = Cliente::find($clienteId);
        if ($cliente) {
            $cliente->delete();
            session()->flash('message', 'Cliente eliminado exitosamente.');
        }
    }

    public function render()
    {
        $clientes = Cliente::query()
            ->when($this->search, function($query) {
                $query->where('razon social', 'like', '%' . $this->search . '%')
                      ->orWhere('correo', 'like', '%' . $this->search . '%')
                      ->orWhere('telefono', 'like', '%' . $this->search . '%');
            })
            ->paginate($this->perPage);

        return view('livewire.admin.clientes.index', [
            'clientes' => $clientes
        ]);
    }
}
