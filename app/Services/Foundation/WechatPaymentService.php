<?php

namespace App\Services\Foundation;

use App\Contracts\ServiceInterface;
use App\Models\Foundation\WechatPayment;
use App\Models\Mall\Order;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Provider\Wechat;
use Yansongda\Supports\Collection;

class WechatPaymentService implements ServiceInterface
{
    /**
     * JSAPI下单（待完善）
     *
     * @param  Order  $order  订单
     *
     * @return array 下单数据
     */
    public function makeOrder(Order $order): array
    {
        return [];
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
}
