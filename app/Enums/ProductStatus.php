<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Up = 'up';

    case Rejected = 'rejected';

    case Down = 'down';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '审核中',
            self::Up => '上架中',
            self::Rejected => '被驳回',
            self::Down => '已下架',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Up => 'emerald',
            self::Rejected => 'red',
            self::Down => 'slate',
        };
    }
}
