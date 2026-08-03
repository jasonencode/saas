<?php

namespace App\Enums\Foundation;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum SearchLanguage: string implements HasLabel, HasDescription
{
    case Simple = 'simple';

    case English = 'english';

    case Chinese = 'chinese';

    public function getLabel(): string
    {
        return match ($this) {
            self::Simple => '不分词',
            self::English => '英文',
            self::Chinese => '中文',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Simple => '按字符分割',
            self::English => '英文',
            self::Chinese => '需配置 zhparser',
        };
    }
}
