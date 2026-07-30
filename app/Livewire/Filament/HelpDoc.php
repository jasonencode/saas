<?php

namespace App\Livewire\Filament;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class HelpDoc extends Component
{
    public bool $exists = false;

    public string $html = '';

    public function mount(): void
    {
        $relativePath = rtrim(request()->path(), '/');

        if (empty($relativePath)) {
            $relativePath = 'index';
        }

        $docPath = resource_path("docs/$relativePath.md");

        if (file_exists($docPath)) {
            $this->exists = true;
            $this->html = Str::markdown(file_get_contents($docPath), [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.filament.help-doc');
    }
}
