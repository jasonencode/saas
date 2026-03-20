<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AliyunDnsType: string implements HasLabel
{
    case A = 'A';

    case AAAA = 'AAAA';

    case CNAME = 'CNAME';

    case TXT = 'TXT';

    public function getLabel(): string
    {
        return match ($this) {
            self::A => 'A记录',
            self::AAAA => 'AAAA记录(IPv6)',
            self::CNAME => 'CNAME记录',
            self::TXT => 'TXT记录',
        };
    }
}
