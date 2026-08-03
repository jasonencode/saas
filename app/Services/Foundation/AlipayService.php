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
     * @param  AlipayModel  $payment  支付宝配置
     *
     * @throws ContainerException 容器异常
     * @throws InvalidArgumentException 配置错误
     *
     * @return Alipay 支付宝实例
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
