<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductContentType: string implements HasLabel
{
    case Material = 'material';

    case RichText = 'rich';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Material => '图片集',
            self::RichText => '富文本',
        };
    }
}
