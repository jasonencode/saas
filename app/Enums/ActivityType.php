<?php

namespace App\Enums;

use App\Enums\Traits\EnumMethods;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ActivityType: string implements HasLabel, HasColor
{
    use EnumMethods;

    case Backend = 'backend';

    case Tenant = 'tenant';

    case Default = 'default';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Backend => '超管后台',
            self::Tenant => '租户平台',
            self::Default => '系统',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Backend => 'primary',
            self::Tenant => 'success',
            self::Default => 'info',
        };
    }
}
