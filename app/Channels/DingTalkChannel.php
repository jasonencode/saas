<?php

namespace App\Channels;

use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Dingtalk;
use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Models\GetAccessTokenRequest;
use AlibabaCloud\SDK\Dingtalk\Vrobot_1_0\Dingtalk as RobotDingtalk;
use AlibabaCloud\SDK\Dingtalk\Vrobot_1_0\Models\BatchSendOTOHeaders;
use AlibabaCloud\SDK\Dingtalk\Vrobot_1_0\Models\BatchSendOTORequest;
use AlibabaCloud\SDK\Dingtalk\Vrobot_1_0\Models\BatchSendOTOResponseBody;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Exception\TeaUnableRetryError;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use App\Contracts\Authenticatable;
use App\Contracts\Notification\DingTalkMessage;
use Darabonba\OpenApi\Models\Config;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * composer require alibabacloud/dingtalk
 * 钉钉通知通道
 *
 * 依赖包：
 * - alibabacloud/dingtalk：阿里云官方 SDK，覆盖新版开放平台全部接口，体积较大
 * - 直接使用 Laravel Http 客户端调用 REST 接口，依赖最少
 *
 * 实现过程：
 * 1. 配置：config/services.php 的 dingtalk 段落，对应 .env 的
 *    DINGTALK_APP_KEY、DINGTALK_APP_SECRET、DINGTALK_ROBOT_CODE。
 *    RobotCode 通常等于 AppKey，未配置时自动回退到 AppKey。
 * 2. 获取 access_token：POST https://api.dingtalk.com/v1.0/oauth2/accessToken，
 *    请求体 {"appKey": "...", "appSecret": "..."}，返回 {"accessToken": "...", "expireIn": 7200}。
 *    该接口有调用频率限制，必须缓存，这里用 Cache 缓存 expireIn - 300 秒。
 * 3. 发送工作通知（机器人单聊消息）：
 *    POST https://api.dingtalk.com/v1.0/robot/oToMessages/batchSend
 *    请求头 x-acs-dingtalk-access-token: {accessToken}
 *    请求体 {"robotCode": "...", "userIds": ["..."], "msgKey": "sampleText", "msgParam": "{...}"}
 *    注意 msgParam 必须是 JSON 字符串（json_encode 后传入），不是数组；userIds 单次最多 100 个。
 *    msgKey 常用值：sampleText（文本）、sampleMarkdown（Markdown）、
 *    sampleLink（链接）、sampleActionCard（卡片）。
 * 4. 消息内容由 toDingTalk() 返回的 DingTalkMessage 构建，通道只负责发送。
 * 5. 发送失败的判定：HTTP 200 不代表成功。这里是 SDK 调用，非 2xx 会自动抛异常；
 *    业务层面的部分失败通过响应体的 invalidStaffIdList（无效用户）与
 *    flowControlledStaffIdList（被流控）返回，两者只记 warning 日志，不重试。
 * 6. 钉钉 userId 需提前绑定：可在 socialites 表写入 provider = 'DingTalk' 的记录，
 *    或在 users 表新增 dingtalk_userid 字段，由通知类的 toDingTalk() 自行解析。
 * 7. 群机器人 Webhook 方式走另一套接口（oapi.dingtalk.com/robot/send），
 *    无需 access_token，本通道未覆盖。
 *
 * @see https://open.dingtalk.com/document/orgapp/robot-overview
 */
class DingTalkChannel
{
    /**
     * 发送钉钉通知
     *
     * @param  Authenticatable  $user  通知用户
     * @param  Notification  $notification  通知内容
     *
     * @throws InvalidArgumentException 通知不支持钉钉发送
     * @throws RuntimeException 钉钉应用配置缺失
     * @throws TeaError 钉钉接口返回业务错误
     * @throws TeaUnableRetryError 钉钉接口重试后仍失败
     */
    public function send(Authenticatable $user, Notification $notification): void
    {
        if (!method_exists($notification, 'toDingTalk')) {
            throw new InvalidArgumentException('The notification is not supported.');
        }

        /** @var DingTalkMessage|null $message */
        $message = $notification->toDingTalk($user);

        if (!$message) {
            return;
        }

        $userIds = $message->getUserIds();

        if (empty($userIds)) {
            return;
        }

        $config = new Config([
            'protocol' => 'https',
            'regionId' => 'central',
            'readTimeout' => 10000,
            'connectTimeout' => 10000,
        ]);

        // 先校验配置并换取令牌，避免配置缺失时抛出的却是 SDK 层面的错误
        $robotCode = $this->getRobotCode();
        $accessToken = $this->getAccessToken($config);

        $request = new BatchSendOTORequest([
            'robotCode' => $robotCode,
            'userIds' => $userIds,
            'msgKey' => $message->getMsgKey(),
            'msgParam' => json_encode($message->getMsgParam(), JSON_UNESCAPED_UNICODE),
        ]);

        $headers = new BatchSendOTOHeaders([
            'xAcsDingtalkAccessToken' => $accessToken,
        ]);

        $response = (new RobotDingtalk($config))->batchSendOTOWithOptions($request, $headers, new RuntimeOptions([]));

        $this->reportPartialFailure($response->body);
    }

    /**
     * 获取 access_token（带缓存）
     *
     * @param  Config  $config  SDK 配置
     *
     * @throws RuntimeException 钉钉应用配置缺失或令牌获取失败
     *
     * @return string access_token
     */
    protected function getAccessToken(Config $config): string
    {
        $appKey = (string) config('services.dingtalk.app_key');
        $appSecret = (string) config('services.dingtalk.app_secret');

        if ($appKey === '' || $appSecret === '') {
            throw new RuntimeException('钉钉应用配置缺失，请检查 DINGTALK_APP_KEY 与 DINGTALK_APP_SECRET');
        }

        $cacheKey = 'dingtalk:access_token:'.$appKey;
        $token = Cache::get($cacheKey);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $response = (new Dingtalk($config))->getAccessToken(new GetAccessTokenRequest([
            'appKey' => $appKey,
            'appSecret' => $appSecret,
        ]));

        $token = (string) $response->body?->accessToken;

        if ($token === '') {
            throw new RuntimeException('钉钉 access_token 获取失败');
        }

        $expireIn = (int) ($response->body?->expireIn ?? 7200);
        Cache::put($cacheKey, $token, max($expireIn - 300, 60));

        return $token;
    }

    /**
     * 获取机器人编码
     *
     * @throws RuntimeException 钉钉机器人配置缺失
     *
     * @return string 机器人编码
     */
    protected function getRobotCode(): string
    {
        $robotCode = (string) (config('services.dingtalk.robot_code') ?: config('services.dingtalk.app_key'));

        if ($robotCode === '') {
            throw new RuntimeException('钉钉机器人配置缺失，请检查 DINGTALK_ROBOT_CODE');
        }

        return $robotCode;
    }

    /**
     * 记录部分失败（无效接收人、被流控）
     *
     * @param  BatchSendOTOResponseBody|null  $body  响应体
     */
    protected function reportPartialFailure(?BatchSendOTOResponseBody $body): void
    {
        if ($body === null) {
            return;
        }

        if (!empty($body->invalidStaffIdList)) {
            Log::warning('钉钉通知存在无效接收人', ['user_ids' => $body->invalidStaffIdList]);
        }

        if (!empty($body->flowControlledStaffIdList)) {
            Log::warning('钉钉通知被流控', ['user_ids' => $body->flowControlledStaffIdList]);
        }
    }
}
