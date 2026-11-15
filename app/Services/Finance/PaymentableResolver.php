<?php

namespace App\Services\Finance;

use App\Contracts\ShouldPayment;
use App\Models\Finance\RechargeOrder;
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
        'recharge' => RechargeOrder::class,
    ];

    /**
     * 获取可支付主体的应付金额
     *
     * 用于创建支付单时从业务模型取真实金额，避免客户端伪造低价买单。
     *
     * @param  Model&ShouldPayment  $paymentable  可支付主体
     *
     * @return string|null 应付金额（两位小数字符串），不支持时返回 null
     */
    public static function amountOf(Model $paymentable): ?string
    {
        if ($paymentable instanceof ShouldPayment) {
            return $paymentable->getTotalAmount();
        }

        return null;
    }

    /**
     * 根据类型标识和 ID 解析可支付主体
     *
     * 同时按用户与租户收窄查询，避免把他人业务单据绑定到自己的支付单。
     *
     * @param  string  $type  类型标识
     * @param  int  $id  业务单据 ID
     * @param  int|null  $userId  所属用户 ID
     * @param  int|null  $tenantId  所属租户 ID
     *
     * @throws InvalidArgumentException 当类型不存在时
     *
     * @return Model|null 可支付主体
     */
    public static function resolve(string $type, int $id, ?int $userId = null, ?int $tenantId = null): ?Model
    {
        $class = self::TYPES[$type] ?? null;

        if (!$class) {
            throw new InvalidArgumentException("不支持的支付关联类型: {$type}");
        }

        return $class::query()
            ->whereKey($id)
            ->when($userId !== null, static fn ($query) => $query->where('user_id', $userId))
            ->when($tenantId !== null, static fn ($query) => $query->where('tenant_id', $tenantId))
            ->first();
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
        $key = array_search($class, self::TYPES, true);

        return $key === false ? null : $key;
    }
}
