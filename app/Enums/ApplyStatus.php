<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApplyStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Approved = 'approved';

    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '申请中',
            self::Approved => '已批准',
            self::Rejected => '已拒绝',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'primary',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
