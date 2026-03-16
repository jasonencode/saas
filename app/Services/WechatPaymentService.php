<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WechatPayment;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Pay\Application;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WechatPaymentService
{
    /**
     * JSAPI下单，待完善
     *
     * @param  Order  $order
     * @return array
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function makeOrder(Order $order): array
    {
        $app = $this->initPayment($order->chapter->wechatPayment);
        $appId = $order->chapter->wechatPayment->wechat->app_id;

        $response = $app->getClient()->postJson('v3/pay/transactions/jsapi', [
            'appid' => $appId,
            'mchid' => $order->chapter->wechatPayment->mch_id,
            'description' => '商品订单No:'.$order->no,
            'out_trade_no' => $order->no,
            'notify_url' => route('payment.notify', $order->chapter->wechatPayment),
            'amount' => [
                'total' => (int) $order->amount * 100,
                'currency' => 'CNY',
            ],
            'payer' => [
                'openid' => $order->user->username,
            ],
        ]);

        $utils = $app->getUtils();

        $signType = 'RSA';
        $prepayId = $response->offsetGet('prepay_id');

        return $utils->buildBridgeConfig($prepayId, $appId, $signType);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function initPayment(WechatPayment $payment): Application
    {
        if ($payment->isEnabled()) {
            $config = [
                'mch_id' => $payment->mch_id,
                'certificate' => $payment->public_key,
                'private_key' => $payment->private_key,
                'secret_key' => $payment->secret,
                'platform_certs' => [
                    // 平台证书：微信支付 APIv3 平台证书，需要使用工具下载
                    // 下载工具：https://github.com/wechatpay-apiv3/CertificateDownloader

                    // 如果是「平台证书」模式
                    //    可简写使用平台证书文件绝对路径
                    // '/path/to/wechatpay/cert.pem',

                    // 如果是「微信支付公钥」模式
                    //    使用Key/Value结构， key为微信支付公钥ID，value为微信支付公钥文件绝对路径
                    // '{$pubKeyId}' => '/path/to/wechatpay/pubkey.pem',
                ],
                'http' => [
                    'throw' => false,
                    'timeout' => 5.0,
                ],
            ];
        } else {
            throw new InvalidArgumentException('微信公众号配置错误');
        }

        return new Application($config);
    }
}
