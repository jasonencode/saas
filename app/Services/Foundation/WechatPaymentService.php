<?php

namespace App\Services\Foundation;

use App\Contracts\ServiceInterface;
use App\Models\Foundation\WechatPayment;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Illuminate\Support\Str;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Plugin\Wechat\V3\Pay\Refund\QueryPlugin as QueryRefundPlugin;
use Yansongda\Pay\Provider\Wechat;
use Yansongda\Supports\Collection;

/**
 * 微信支付服务
 *
 * 注意：yansongda 的 Pay::config() 是进程级全局配置，同一进程不能并发使用不同商户。
 * 当前运行在 artisan serve / Horizon 单请求模型下；迁移到 Octane/Swoole 前需要按请求隔离配置。
 */
class WechatPaymentService implements ServiceInterface
{
    /**
     * JSAPI下单
     *
     * @param  WechatPayment  $payment  微信支付配置
     * @param  string  $openid  用户 openid
     * @param  string  $orderNo  商户订单号
     * @param  int  $amount  金额（分）
     * @param  string  $description  商品描述
     * @param  string|null  $notifyUrl  回调地址
     *
     * @return array 下单数据（供前端调起支付）
     */
    public function makeOrder(
        WechatPayment $payment,
        string $openid,
        string $orderNo,
        int $amount,
        string $description,
        ?string $notifyUrl = null,
    ): array {
        $wechat = $this->initPayment($payment);

        $params = [
            'description' => $description,
            'out_trade_no' => $orderNo,
            'amount' => [
                'total' => $amount,
                'currency' => 'CNY',
            ],
            'payer' => [
                'openid' => $openid,
            ],
        ];

        if ($notifyUrl) {
            $params['notify_url'] = $notifyUrl;
        }

        try {
            // mp() 已包含 JSAPI 调起参数签名（appId/timeStamp/nonceStr/package/paySign）
            $result = $wechat->mp($params);

            return [
                'time_stamp' => (string) $result['timeStamp'],
                'nonce_str' => (string) $result['nonceStr'],
                'package' => (string) $result['package'],
                'sign_type' => (string) $result['signType'],
                'pay_sign' => (string) $result['paySign'],
            ];
        } finally {
            $payment->cleanupTempFiles();
        }
    }

    /**
     * 原路退回（微信退款）
     *
     * 调用微信 v3 退款接口，按原支付单退回款项；金额单位为分，且必须与原支付单总额一致口径。
     *
     * @param  WechatPayment  $payment  支付配置
     * @param  string  $outTradeNo  原支付单号（商户侧）
     * @param  string  $outRefundNo  商户退款单号
     * @param  int  $refundAmount  退款金额（分）
     * @param  int  $totalAmount  原支付单总额（分）
     * @param  string|null  $reason  退款原因
     * @param  string|null  $notifyUrl  退款结果回调地址
     * @param  bool  $requireEnabled  是否要求配置启用（原路退回可放宽）
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     *
     * @return Collection 微信退款结果（含 status / refund_id 等）
     */
    public function refund(
        WechatPayment $payment,
        string $outTradeNo,
        string $outRefundNo,
        int $refundAmount,
        int $totalAmount,
        ?string $reason = null,
        ?string $notifyUrl = null,
        bool $requireEnabled = true,
    ): Collection {
        $wechat = $this->initPayment($payment, $requireEnabled);

        $params = [
            'out_trade_no' => $outTradeNo,
            'out_refund_no' => $outRefundNo,
            'amount' => [
                'refund' => $refundAmount,
                'total' => $totalAmount,
                'currency' => 'CNY',
            ],
        ];

        if (!empty($reason)) {
            $params['reason'] = Str::limit($reason, 80, '');
        }

        if ($notifyUrl) {
            $params['notify_url'] = $notifyUrl;
        }

        try {
            return $wechat->refund($params);
        } finally {
            $payment->cleanupTempFiles();
        }
    }

    /**
     * 查询单笔退款（按商户退款单号）
     *
     * @param  WechatPayment  $payment  支付配置
     * @param  string  $outRefundNo  商户退款单号
     * @param  bool  $requireEnabled  是否要求配置启用（对账可放宽）
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     *
     * @return Collection 微信退款详情（含 status / refund_id 等）
     */
    public function queryRefund(WechatPayment $payment, string $outRefundNo, bool $requireEnabled = true): Collection
    {
        $wechat = $this->initPayment($payment, $requireEnabled);

        try {
            return $wechat->pay(
                $wechat->mergeCommonPlugins([QueryRefundPlugin::class]),
                ['out_refund_no' => $outRefundNo],
            );
        } finally {
            $payment->cleanupTempFiles();
        }
    }

    /**
     * 关闭微信支付订单
     *
     * @param  WechatPayment  $payment  支付配置
     * @param  string  $outTradeNo  商户支付单号
     * @param  bool  $requireEnabled  是否要求配置启用
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     *
     * @return Collection 微信关单结果
     */
    public function close(WechatPayment $payment, string $outTradeNo, bool $requireEnabled = true): Collection
    {
        $wechat = $this->initPayment($payment, $requireEnabled);

        try {
            return $wechat->close(['out_trade_no' => $outTradeNo]);
        } finally {
            $payment->cleanupTempFiles();
        }
    }

    /**
     * 商家转账（现金红包）
     *
     * @param  WechatPayment  $payment  支付配置
     * @param  string  $openid  接收者 openid
     * @param  int  $amount  金额（分）
     * @param  string  $billNo  商户订单号
     * @param  array  $extra  额外参数
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function sendRedpack(
        WechatPayment $payment,
        string $openid,
        int $amount,
        string $billNo,
        array $extra = [],
    ): Collection {
        $wechat = $this->initPayment($payment);

        $params = [
            'appid' => $payment->wechat?->app_id ?? '',
            'out_bill_no' => $billNo,
            'transfer_scene_id' => $extra['transfer_scene_id'] ?? '1000',
            'openid' => $openid,
            'transfer_amount' => $amount,
            'transfer_remark' => $extra['transfer_remark'] ?? $extra['act_name'] ?? '',
            'transfer_scene_report_infos' => [
                [
                    'info_type' => '活动名称',
                    'info_content' => $extra['act_name'] ?? '',
                ],
                [
                    'info_type' => '奖励说明',
                    'info_content' => $extra['wishing'] ?? '恭喜发财',
                ],
            ],
        ];

        if (!empty($extra['user_name'])) {
            $params['user_name'] = $extra['user_name'];
        }

        if (!empty($extra['notify_url'])) {
            $params['notify_url'] = $extra['notify_url'];
        }

        if (!empty($extra['user_recv_perception'])) {
            $params['user_recv_perception'] = $extra['user_recv_perception'];
        }

        if (($extra['use_red_packet'] ?? false) && $amount <= 20000) {
            $params['user_recv_style'] = [
                'type' => 'RED_PACKET',
            ];
        }

        try {
            return $wechat->transfer($params);
        } finally {
            $payment->cleanupTempFiles();
        }
    }

    /**
     * 初始化微信支付
     *
     * @param  WechatPayment  $payment  微信支付配置
     * @param  bool  $requireEnabled  是否要求配置启用（退款/回调/对账可放宽）
     *
     * @throws InvalidArgumentException 配置错误
     * @throws ContainerException 容器异常
     *
     * @return Wechat 微信支付实例
     */
    public function initPayment(WechatPayment $payment, bool $requireEnabled = true): Wechat
    {
        if (!$requireEnabled || $payment->isEnabled()) {
            Pay::config($payment->getConfig());

            return Pay::wechat();
        }

        throw new InvalidArgumentException('微信公众号配置错误');
    }

    /**
     * 处理微信回调（支付 / 退款通用）
     *
     * 验签后返回 decrypt 后的 resource 数据（字段随回调类型不同）。
     *
     * @param  WechatPayment  $payment  微信支付配置
     * @param  bool  $requireEnabled  是否要求配置启用（已停用配置仍需接收原回调）
     *
     * @return array 解密后的回调业务数据
     */
    public function handleNotify(WechatPayment $payment, bool $requireEnabled = true): array
    {
        $wechat = $this->initPayment($payment, $requireEnabled);

        try {
            $result = $wechat->callback();
            $resource = (array) ($result['resource'] ?? []);

            // 解密后的业务数据位于 resource.ciphertext
            return is_array($resource['ciphertext'] ?? null) ? $resource['ciphertext'] : $resource;
        } finally {
            $payment->cleanupTempFiles();
        }
    }
}
