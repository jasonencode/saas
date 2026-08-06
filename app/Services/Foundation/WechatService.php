<?php

namespace App\Services\Foundation;

use App\Contracts\ServiceInterface;
use App\Models\Foundation\Wechat;
use EasyWeChat\OfficialAccount\Application;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WechatService implements ServiceInterface
{
    /**
     * 测试公众号是否成功连接
     *
     * @param  Wechat  $wechat  微信公众号配置
     *
     * @throws Exception 连接失败时抛出
     * @throws TransportExceptionInterface 网络异常
     *
     * @return bool 是否连接成功
     */
    public function testConnection(Wechat $wechat): bool
    {
        try {
            $config = [
                'app_id' => $wechat->app_id,
                'secret' => $wechat->app_secret,
            ];
            $app = new Application($config);
            $app->getClient()->get('cgi-bin/user/get');

            return true;
        } catch (Exception $exception) {
            $error = $exception->getMessage();
            $string = substr($error, strpos($error, '{'));
            $data = json_decode($string, true, 512, JSON_THROW_ON_ERROR);

            throw new RuntimeException($data['errmsg'], $data['errcode']);
        }
    }
}
