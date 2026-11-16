<?php

namespace App\Services\Foundation;

use App\Contracts\ServiceInterface;
use App\Models\Foundation\Wechat;
use EasyWeChat\OfficialAccount\Application;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;

class WechatService implements ServiceInterface
{
    /**
     * 测试公众号是否成功连接
     *
     * 通过获取 access_token 验证 AppID / AppSecret 与网络连通性，
     * 该接口是微信官方稳定接口，错误信息统一为 JSON 编码的 errcode/errmsg。
     *
     * @param  Wechat  $wechat  微信公众号配置
     *
     * @throws RuntimeException 连接失败时抛出
     * @throws TransportExceptionInterface 网络异常
     *
     * @return bool 是否连接成功
     */
    public function testConnection(Wechat $wechat): bool
    {
        try {
            $app = new Application($wechat->getConfig());
            $app->getAccessToken()->getToken();

            return true;
        } catch (Throwable $exception) {
            throw new RuntimeException($this->failureMessage($exception), 0, $exception);
        }
    }

    /**
     * 提取微信错误信息
     *
     * 微信接口异常消息形如 `... {"errcode":40013,"errmsg":"invalid appid"}`，
     * 解析失败时回退到通用提示，避免把内部异常细节直接暴露给后台用户。
     *
     * @param  Throwable  $exception  原始异常
     */
    protected function failureMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $start = strpos($message, '{');

        if ($start !== false) {
            $data = json_decode(substr($message, $start), true);

            if (is_array($data) && !empty($data['errmsg'])) {
                return (string) $data['errmsg'];
            }
        }

        return '微信配置测试失败，请检查 AppID 与 AppSecret';
    }
}
