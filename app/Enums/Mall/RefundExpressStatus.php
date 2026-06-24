<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RefundExpressStatus: string implements HasColor, HasLabel
{
    /**
     * 待发货
     */
    case Pending = 'pending';

    /**
     * 已发货
     */
    case Shipped = 'shipped';

    /**
     * 已签收
     */
    case Received = 'received';

    /**
     * 已验收
     */
    case Checked = 'checked';

    /**
     * 已拒收
     */
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待发货',
            self::Shipped => '已发货',
            self::Received => '已签收',
            self::Checked => '已验收',
            self::Rejected => '已拒收',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Shipped => 'blue',
            self::Received => 'teal',
            self::Checked => 'emerald',
            self::Rejected => 'red',
        };
    }
}
