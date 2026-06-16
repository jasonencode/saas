<?php

namespace App\Services\Foundation;

use App\Contracts\ServiceInterface;
use App\Models\Foundation\Alipay as AlipayModel;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Provider\Alipay;

class AlipayService implements ServiceInterface
{
    /**
     * 初始化支付宝
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function initPayment(AlipayModel $payment): Alipay
    {
        if ($payment->isEnabled()) {
            Pay::config($payment->getConfig());

            return Pay::alipay();
        }

        throw new InvalidArgumentException('支付宝配置错误');
    }
}
