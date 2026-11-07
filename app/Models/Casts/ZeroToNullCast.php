<?php

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * 将 0 转为 null 的整型转换
 *
 * 用于行政区划等以 0 表示顶级节点的树形数据：读取时把 0 映射为 null，
 * 写入时保留原始值。
 *
 * @implements CastsAttributes<int|null, int|null>
 */
class ZeroToNullCast implements CastsAttributes
{
    /**
     * 读取属性
     *
     * @param  Model  $model  模型实例
     * @param  string  $key  属性名
     * @param  mixed  $value  原始值
     * @param  array<string, mixed>  $attributes  原始属性
     *
     * @return int|null 转换后的值
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return ((int) $value) ?: null;
    }

    /**
     * 写入属性
     *
     * @param  Model  $model  模型实例
     * @param  string  $key  属性名
     * @param  mixed  $value  属性值
     * @param  array<string, mixed>  $attributes  原始属性
     *
     * @return int|null 转换后的值
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
