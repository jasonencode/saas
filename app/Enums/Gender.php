<?php

namespace App\Enums;

use App\Enums\Traits\EnumMethods;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasLabel, HasColor
{
    use EnumMethods;

    case Male = 'male';

    case Female = 'female';

    case Secret = 'secret';

    /**
     * 从性别代码创建枚举
     */
    public static function fromCode(int $code): self
    {
        return match ($code) {
            1 => self::Male,
            2 => self::Female,
            default => self::Secret,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Male => '男',
            self::Female => '女',
            self::Secret => '保密',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Male => 'danger',
            self::Female => 'success',
            self::Secret => 'info',
        };
    }

    /**
     * 获取性别代码（用于身份证等场景）
     */
    public function getCode(): int
    {
        return match ($this) {
            self::Male => 1,
            self::Female => 2,
            self::Secret => 0,
        };
    }
}
