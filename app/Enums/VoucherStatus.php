<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * 结算凭据状态
 */
enum VoucherStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';

    case Processing = 'processing';

    case Success = 'success';

    case Failure = 'failure';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待执行',
            self::Processing => '执行中',
            self::Success => '成功',
            self::Failure => '失败',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'primary',
            self::Success => 'success',
            self::Failure => 'danger',
        };
    }
}
