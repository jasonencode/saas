<?php

namespace App\Services\Mall;

use App\Contracts\Orderable;
use App\Models\Mall\Sku;
use App\Models\User\Identity;
use InvalidArgumentException;

class OrderableResolver
{
    /**
     * 可订购类型映射表
     *
     * 键为请求中传入的简短标识，值为模型类名。
     * 新增 Orderable 实现时在此注册即可。
     */
    public const array TYPES = [
        'sku' => Sku::class,
        'identity' => Identity::class,
    ];

    /**
     * 根据类型标识和 ID 解析可订购主体
     *
     * @throws InvalidArgumentException 当类型不存在时
     */
    public static function resolve(string $type, int $id): ?Orderable
    {
        $class = self::TYPES[$type] ?? null;

        if (!$class) {
            throw new InvalidArgumentException("不支持的商品类型: {$type}");
        }

        $model = $class::find($id);

        return $model instanceof Orderable ? $model : null;
    }

    /**
     * 获取所有支持的 type key
     */
    public static function keys(): array
    {
        return array_keys(self::TYPES);
    }
}
