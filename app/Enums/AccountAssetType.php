<?php

namespace App\Enums;

use App\Contracts\AssetInterface;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountAssetType: string implements HasLabel, HasColor, AssetInterface
{
    case Balance = 'balance';

    case Points = 'points';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Balance => '余额',
            self::Points => '积分',
        };
    }

    public function getField(): string
    {
        return match ($this) {
            self::Balance => 'balance',
            self::Points => 'points',
        };
    }

    /**
     * 根据字段名获取资产类型
     *
     * @param string $field 字段名
     * @return AccountAssetType|null
     */
    public static function fromField(string $field): ?self
    {
        return match ($field) {
            'balance' => self::Balance,
            'points' => self::Points,
            default => null,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Balance => 'primary',
            self::Points => 'success',
        };
    }
}
