<?php

namespace App\Filament\Backend\Widgets;

use Filament\Widgets\Widget;

class SystemInfoWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.system-info';

    public function __construct()
    {
    }

    public function getViewData(): array
    {
        $version = file_get_contents(storage_path('version'));

        return [
            'version' => $version,
        ];
    }
}
