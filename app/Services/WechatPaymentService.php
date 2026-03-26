<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Models\Order;
use App\Models\WechatPayment;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Provider\Wechat;

class WechatPaymentService implements ServiceInterface
{
    /**
     * JSAPI下单，待完善
     *
     * @param  Order  $order
     * @return array
     */
    public function makeOrder(Order $order): array
    {
        return [];
    }

    /**
     * @throws InvalidArgumentException
     * @throws ContainerException
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
