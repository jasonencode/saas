<?php

namespace App\Services\Foundation;

use App\Contracts\ServiceInterface;
use App\Models\Foundation\WechatPayment;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Illuminate\Support\Str;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Provider\Wechat;
use Yansongda\Supports\Collection;

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
            'appid' => $payment->wechat?->app_id ?? '',
            'openid' => $openid,
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
            $result = $wechat->post('v3/pay/transactions/jsapi', $params);

            $prepayId = $result['prepay_id'] ?? '';

            // 生成前端调起支付所需的签名参数
            $timeStamp = (string) time();
            $nonceStr = Str::random(32);
            $package = "prepay_id={$prepayId}";
            $signType = 'RSA';

            // 签名内容: appId + timeStramp + nonceStr + package
            $message = "{$payment->wechat?->app_id}\n{$timeStamp}\n{$nonceStr}\n{$package}\n";
            $sign = $this->sign($message, $payment->private_key);

            return [
                'time_stamp' => $timeStamp,
                'nonce_str' => $nonceStr,
                'package' => $package,
                'sign_type' => $signType,
                'pay_sign' => $sign,
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
    ): Collection {
        $wechat = $this->initPayment($payment);

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

        try {
            return $wechat->post('v3/refund/domestic/refunds', $params);
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
            return $wechat->post('v3/fund-app/mch-transfer/transfer-bills', $params);
        } finally {
            $payment->cleanupTempFiles();
        }
    }

    /**
     * 初始化微信支付
     *
     * @param  WechatPayment  $payment  微信支付配置
     *
     * @throws InvalidArgumentException 配置错误
     * @throws ContainerException 容器异常
     *
     * @return Wechat 微信支付实例
     */
    public function initPayment(WechatPayment $payment): Wechat
    {
        if ($payment->isEnabled()) {
            Pay::config($payment->getConfig());

            return Pay::wechat();
        }

        throw new InvalidArgumentException('微信公众号配置错误');
    }

    /**
     * 处理微信支付回调
     *
     * @param  WechatPayment  $payment  微信支付配置
     *
     * @return array 解密后的回调数据
     */
    public function handleNotify(WechatPayment $payment): array
    {
        $wechat = $this->initPayment($payment);

        try {
            $data = $wechat->handleNotify(function ($notify, $parsed) {
                return $parsed;
            });

            return $data;
        } finally {
            $payment->cleanupTempFiles();
        }
    }

    /**
     * RSA 签名
     *
     * @param  string  $message  待签名内容
     * @param  string  $privateKey  商户私钥（PEM 格式）
     *
     * @return string 签名（Base64 编码）
     */
    private function sign(string $message, string $privateKey): string
    {
        $key = openssl_pkey_get_private($privateKey);

        if (!$key) {
            throw new InvalidArgumentException('无法加载商户私钥');
        }

        openssl_sign($message, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);

        return base64_encode($signature);
    }
}
