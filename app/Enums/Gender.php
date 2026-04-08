<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasColor, HasLabel
{
    case Male = 'male';

    case Female = 'female';

    case Secret = 'secret';

    public static function fromCode(int $code): self
    {
        return match ($code) {
            1 => self::Male,
            2 => self::Female,
            default => self::Secret,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Male => '男',
            self::Female => '女',
            self::Secret => '保密',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Male => 'danger',
            self::Female => 'success',
            self::Secret => 'info',
        };
    }

    public function getCode(): int
    {
        return match ($this) {
            self::Male => 1,
            self::Female => 2,
            self::Secret => 0,
        };
    }
}
