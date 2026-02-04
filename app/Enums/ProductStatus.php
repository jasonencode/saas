<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';

    case Approved = 'approved';

    case Up = 'up';

    case Rejected = 'rejected';

    case Down = 'down';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '审核中',
            self::Approved => '审核通过',
            self::Up => '上架中',
            self::Rejected => '被驳回',
            self::Down => '已下架',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'default',
            self::Approved => 'info',
            self::Up => 'success',
            self::Rejected => 'danger',
            self::Down => 'warning',
        };
    }
}
