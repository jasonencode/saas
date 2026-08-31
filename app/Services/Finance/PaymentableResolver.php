<?php

namespace App\Services\Finance;

use App\Models\Mall\Order;
use App\Models\Model;
use InvalidArgumentException;

class PaymentableResolver
{
    /**
     * 可支付类型映射表
     *
     * 键为请求中传入的简短标识，值为模型类名。
     * 新增可支付主体时在此注册即可。
     */
    public const array TYPES = [
        'order' => Order::class,
    ];

    /**
     * 根据类型标识和 ID 解析可支付主体
     *
     * @throws InvalidArgumentException 当类型不存在时
     */
    public static function resolve(string $type, int $id): ?Model
    {
        $class = self::TYPES[$type] ?? null;

        if (!$class) {
            throw new InvalidArgumentException("不支持的支付关联类型: {$type}");
        }

        return $class::find($id);
    }

    /**
     * 获取所有支持的 type key
     */
    public static function keys(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * 由模型类名反查短键（用于响应输出）
     */
    public static function keyFor(?string $class): ?string
    {
        if ($class === null) {
            return null;
        }

        $key = array_search($class, self::TYPES, true);

        return $key === false ? $class : $key;
    }
}
