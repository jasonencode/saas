<?php

namespace App\Services\Foundation;

use App\Contracts\ServiceInterface;
use App\Models\Foundation\WechatPayment;
use App\Models\Mall\Order;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Illuminate\Support\Facades\Log;
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
     * 发送微信红包
     *
     * @param  WechatPayment  $payment  支付配置
     * @param  string  $openid  接收者 openid
     * @param  int  $amount  金额（分）
     * @param  string  $billNo  商户订单号
     * @param  array  $extra  额外参数（send_name, wishing, act_name, remark 等）
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

        $params = array_merge([
            'mch_billno' => $billNo,
            'send_name' => $extra['send_name'] ?? '',
            're_openid' => $openid,
            'total_amount' => $amount,
            'total_num' => 1,
            'wishing' => $extra['wishing'] ?? '',
            'act_name' => $extra['act_name'] ?? '',
            'remark' => $extra['remark'] ?? '',
        ], $extra);

        try {
            return $wechat->redpack($params);
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
