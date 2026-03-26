<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Models\Alipay as AlipayModel;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Provider\Alipay;

class AlipayService implements ServiceInterface
{
    /**
     * 初始化支付宝
     *
     * @param  AlipayModel  $payment
     * @return Alipay
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function initPayment(AlipayModel $payment): Alipay
    {
        if ($payment->isEnabled()) {
            Pay::config($payment->getConfig());

            return Pay::alipay();
        }

        throw new InvalidArgumentException('微信公众号配置错误');
    }
}
