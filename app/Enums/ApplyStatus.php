<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ApplyStatus: string implements HasLabel
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
}
