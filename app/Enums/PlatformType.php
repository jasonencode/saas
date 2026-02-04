<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PlatformType: string implements HasLabel
{
    case Android = 'android';

    case Ios = 'ios';

    case AndroidPad = 'apad';

    case Ipad = 'ipad';

    case Wmp = 'wmp';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Android => '安卓',
            self::Ios => 'IOS',
            self::AndroidPad => '安卓平板',
            self::Ipad => 'IPAD平板',
            self::Wmp => '微信小程序',
        };
    }
}

