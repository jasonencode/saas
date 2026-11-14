<?php

namespace App\Support\Mall;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Models\Mall\StoreConfigure;

/**
 * 极速退款准入判定
 *
 * 仅未发货的快递订单适用：平台与店铺开关同时开启、存在支持自动原路退回的已支付支付单，
 * 且退款金额与单用户单日次数都在生效上限之内。
 */
final class ExpressRefundRule implements ServiceInterface
{
    /**
     * 支持自动原路退回的支付通道
     */
    private const array SUPPORTED_GATEWAYS = [
        PaymentGateway::Balance,
        PaymentGateway::Wechat,
    ];

    /**
     * 是否命中极速退款规则
     *
     * @param  Order  $order  订单
     * @param  string  $amount  本笔退款金额
     *
     * @return bool 是否命中
     */
    public function isEligible(Order $order, string $amount): bool
    {
        if (!(bool) config('custom.mall.express_refund.enabled', false)) {
            return false;
        }

        if ($order->fulfillment_type !== FulfillmentType::Mail) {
            return false;
        }

        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            return false;
        }

        $configure = StoreConfigure::whereKey($order->tenant_id)->first();

        if (!$configure?->express_refund_enabled) {
            return false;
        }

        if (!$this->supportsAutoRefund($order)) {
            return false;
        }

        if (!$this->withinAmount($amount, $configure)) {
            return false;
        }

        return $this->withinDailyLimit($order, $configure);
    }

    /**
     * 是否存在支持自动原路退回的已支付支付单
     *
     * @param  Order  $order  订单
     *
     * @return bool 是否支持自动原路退回
     */
    private function supportsAutoRefund(Order $order): bool
    {
        return $order->paymentOrders()
            ->where('status', PaymentStatus::Paid)
            ->whereIn('gateway', self::SUPPORTED_GATEWAYS)
            ->exists();
    }

    /**
     * 退款金额是否在生效上限内（平台与店铺上限取较小值，0 表示不限制）
     *
     * @param  string  $amount  本笔退款金额
     * @param  StoreConfigure  $configure  店铺配置
     *
     * @return bool 是否在上限内
     */
    private function withinAmount(string $amount, StoreConfigure $configure): bool
    {
        $limit = $this->smallestPositive([
            (string) config('custom.mall.express_refund.max_amount', 0),
            (string) $configure->express_refund_max_amount,
        ]);

        return $limit === null || bccomp($amount, $limit, 2) <= 0;
    }

    /**
     * 单用户当日极速退款次数是否在生效上限内
     *
     * @param  Order  $order  订单
     * @param  StoreConfigure  $configure  店铺配置
     *
     * @return bool 是否在次数上限内
     */
    private function withinDailyLimit(Order $order, StoreConfigure $configure): bool
    {
        $limit = $this->smallestPositive([
            (string) config('custom.mall.express_refund.daily_limit', 0),
            (string) $configure->express_refund_daily_limit,
        ]);

        if ($limit === null) {
            return true;
        }

        $used = Refund::query()
            ->where('user_id', $order->user_id)
            ->where('is_express', true)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return $used < (int) $limit;
    }

    /**
     * 取非零候选项中的最小值
     *
     * @param  array<int, string>  $values  候选上限
     *
     * @return string|null 最小上限，全部为 0 时返回 null（不限制）
     */
    private function smallestPositive(array $values): ?string
    {
        $limit = null;

        foreach ($values as $value) {
            $value = number_format((float) $value, 2, '.', '');

            if (bccomp($value, '0', 2) !== 1) {
                continue;
            }

            if ($limit === null || bccomp($value, $limit, 2) === -1) {
                $limit = $value;
            }
        }

        return $limit;
    }
}
