<?php

namespace App\Livewire\Auth;

use App\Models\Campana;
use Livewire\Component;

class CarruselCampanas extends Component
{
    public $campanas = [];
    public $currentIndex = 0;
    public $autoplayInterval = 5000; // 5 segundos por slide

    public function mount()
    {
        $this->loadCampanas();
    }

    public function loadCampanas()
    {
        // Cargar campañas activas
        $this->campanas = Campana::activas()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function nextSlide()
    {
        if (count($this->campanas) > 0) {
            $this->currentIndex = ($this->currentIndex + 1) % count($this->campanas);
        }
    }

    public function prevSlide()
    {
        if (count($this->campanas) > 0) {
            $this->currentIndex = ($this->currentIndex - 1 + count($this->campanas)) % count($this->campanas);
        }
    }

    public function setSlide($index)
    {
        if ($index >= 0 && $index < count($this->campanas)) {
            $this->currentIndex = $index;
        }
    }

    public function render()
    {
        return view('livewire.auth.carrusel-campanas');
    }
}
