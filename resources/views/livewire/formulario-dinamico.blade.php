<div>
    @if ($zona)
        <form wire:submit.prevent="guardar">
            <h2 class="text-xl font-bold mb-4">{{ $zona->nombre }}</h2>

            @foreach ($campos as $campo)
                {!! $this->renderizarCampo($campo, $formulario, 'formulario') !!}
            @endforeach

            <div class="mt-6">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Guardar
                </button>
            </div>

            @if (session()->has('message'))
                <div class="mt-4 p-4 bg-green-100 text-green-700 rounded">
                    {{ session('message') }}
                </div>
            @endif
        </form>
    @else
        <div class="p-4 bg-yellow-100 text-yellow-700 rounded">
            Seleccione una zona para mostrar su formulario.
        </div>
    @endif
</div>
