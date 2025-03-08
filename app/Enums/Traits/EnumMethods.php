<?php

namespace App\Enums\Traits;

trait EnumMethods
{
    /**
     * 获取用于选择框的数据
     *
     * @return array<array{key: int|string, value: string}>
     */
    public static function forSelect(): array
    {
        return array_map(
            fn($case) => [
                'key' => $case->value,
                'value' => $case->getLabel(),
            ],
            self::cases()
        );
    }

    /**
     * 获取枚举键值对
     *
     * @return array<string, int|string>
     */
    public static function pairs(): array
    {
        return array_combine(self::keys(), self::values());
    }

    /**
     * 获取枚举键名列表
     *
     * @return array<string>
     */
    public static function keys(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * 获取枚举值列表
     *
     * @return array<int|string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * 检查值是否存在
     *
     * @param  int|string  $value
     * @return bool
     */
    public static function hasValue(int|string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
