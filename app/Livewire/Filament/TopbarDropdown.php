<?php

namespace App\Livewire\Filament;

use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TopbarDropdown extends Component
{
    public string $width = '300px';

    public string|Heroicon $icon = Heroicon::OutlinedUserCircle;

    public string $label = '账户';

    public string $tooltip = '';

    public string $header = '账户';

    public string $empty = '账户';

    public function render(): View
    {
        return view('livewire.filament.topbar-dropdown');
    }
}
