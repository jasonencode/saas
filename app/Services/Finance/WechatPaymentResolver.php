<?php

namespace App\Services\Finance;

use App\Models\Finance\PaymentOrder;
use App\Models\Foundation\WechatPayment;
use RuntimeException;

/**
 * 微信商户配置解析
 *
 * 支付单必须锁定下单时使用的商户配置：支付、回调、退款、对账统一从这里取，
 * 禁止在租户为空时回退到全平台任意一条配置。
 */
class WechatPaymentResolver
{
    /**
     * 解析支付单对应的商户配置
     *
     * @param  PaymentOrder  $payment  支付单
     * @param  bool  $requireEnabled  是否要求配置处于启用状态（退款/回调可放宽）
     *
     * @throws RuntimeException 未配置微信支付或配置已停用
     *
     * @return WechatPayment 商户配置
     */
    public static function forPayment(PaymentOrder $payment, bool $requireEnabled = true): WechatPayment
    {
        $config = null;

        if ($payment->wechat_payment_id) {
            // 原配置被软删后仍需用于回调验签与退款
            $config = WechatPayment::withTrashed()->whereKey($payment->wechat_payment_id)->first();
        }

        // 支付单记录的配置必须属于同一租户，避免历史脏数据把退款打到其他租户
        if ($config && $payment->tenant_id !== null && (int) $config->tenant_id !== (int) $payment->tenant_id) {
            $config = null;
        }

        $config ??= self::forTenant($payment->tenant_id, $requireEnabled);

        if (!$config) {
            throw new RuntimeException('未配置微信支付');
        }

        if ($requireEnabled && !$config->isEnabled()) {
            throw new RuntimeException('微信支付配置已停用');
        }

        return $config;
    }

    /**
     * 解析租户的微信商户配置
     *
     * @param  int|null  $tenantId  租户 ID（为空时返回 null，禁止全表回退）
     * @param  bool  $requireEnabled  是否只取启用配置
     *
     * @return WechatPayment|null 商户配置
     */
    public static function forTenant(?int $tenantId, bool $requireEnabled = true): ?WechatPayment
    {
        if ($tenantId === null) {
            return null;
        }

        return WechatPayment::query()
            ->where('tenant_id', $tenantId)
            ->when($requireEnabled, static fn ($query) => $query->where('status', true))
            ->orderByDesc('status')
            ->orderBy('id')
            ->first();
    }
}
