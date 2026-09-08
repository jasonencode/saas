<?php

namespace App\Enums\Content;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SuggestStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Resolved = 'resolved';

    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待处理',
            self::Resolved => '已回复',
            self::Closed => '已关闭',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Resolved => 'success',
            self::Closed => 'gray',
        };
    }
}
