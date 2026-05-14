<?php

namespace App\Livewire\Filament;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Homepage extends Component
{
    public function render(): View
    {
        return view('livewire.filament.homepage');
    }
}
