<?php

namespace App\Enums;

use App\Extensions\Certificate\PrivateKey;
use Filament\Support\Contracts\HasLabel;

/**
 * 证书签名方式
 */
enum CertificateSignType: string implements HasLabel
{
    case EC256 = 'EC256';

    case EC384 = 'EC384';

    case EC512 = 'EC512';

    case RSA1024 = 'RSA1024';

    case RSA2048 = 'RSA2048';

    case RSA4096 = 'RSA4096';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EC256 => 'EC-256',
            self::EC384 => 'EC-384',
            self::EC512 => 'EC-512',
            self::RSA1024 => 'RSA-1024',
            self::RSA2048 => 'RSA-2048',
            self::RSA4096 => 'RSA-4096',
        };
    }

    public function getPrivateKey(): PrivateKey
    {
        return match ($this) {
            self::EC256 => PrivateKey::makeEcKey(256),
            self::EC384 => PrivateKey::makeEcKey(),
            self::EC512 => PrivateKey::makeEcKey(512),
            self::RSA1024 => PrivateKey::makeRsaKey(1024),
            self::RSA2048 => PrivateKey::makeRsaKey(2048),
            self::RSA4096 => PrivateKey::makeRsaKey(),
        };
    }
}
