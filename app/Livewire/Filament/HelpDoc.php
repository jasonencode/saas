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

        // tenant 面板 URL 含动态租户 slug（tenant/{slug}/...），匹配文档时跳过该段
        $segments = explode('/', $relativePath);

        if (($segments[0] ?? null) === 'tenant' && isset($segments[1]) && $segments[1] !== 'tenant-expired') {
            array_splice($segments, 1, 1);
            $relativePath = implode('/', $segments);
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
