<?php

namespace App\Enums\Content;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TagType: string implements HasColor, HasLabel
{
    case Content = 'content';

    case Product = 'product';

    public function getLabel(): string
    {
        return match ($this) {
            self::Content => '内容标签',
            self::Product => '商品标签',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Content => 'primary',
            self::Product => 'danger',
        };
    }
}
