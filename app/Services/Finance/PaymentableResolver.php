<?php

namespace App\Services\Finance;

use App\Models\Mall\Order;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class PaymentableResolver
{
    /**
     * 可支付类型映射表
     *
     * 键为请求中传入的简短标识，值为模型类名。
     * 新增可支付模型时在此注册即可。
     */
    public const array TYPES = [
        'order' => Order::class,
    ];

    /**
     * 获取可支付主体的应付金额
     *
     * 用于创建支付单时从业务模型取真实金额，避免客户端伪造低价买单。
     *
     * @param  Model  $paymentable  可支付主体
     *
     * @return float|null 应付金额，不支持时返回 null
     */
    public static function amountOf(Model $paymentable): ?float
    {
        return match (true) {
            $paymentable instanceof Order => $paymentable->getTotalAmount(),
            default => null,
        };
    }

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
     * 根据模型类名反查 type key
     */
    public static function keyFor(string $class): ?string
    {
        return array_search($class, self::TYPES, true) ?: null;
    }
}
