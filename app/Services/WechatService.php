<?php

namespace App\Services;

use App\Models\Wechat;
use EasyWeChat\OfficialAccount\Application;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WechatService
{
    /**
     * 测试公众号是否成功连接
     *
     * @param  Wechat  $wechat
     * @return bool
     * @throws Exception|TransportExceptionInterface
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
