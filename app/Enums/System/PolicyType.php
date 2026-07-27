<?php

namespace App\Enums\System;

use Filament\Support\Contracts\HasLabel;

enum PolicyType: string implements HasLabel
{
    case Page = 'page';

    case Button = 'button';

    public function getLabel(): string
    {
        return match ($this) {
            self::Page => '页面',
            self::Button => '按钮',
        };
    }
}
