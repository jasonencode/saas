<?php

namespace App\Jobs\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Enums\Foundation\SocialiteProvider;
use App\Jobs\BaseJob;
use App\Models\Campaign\RedpackCode;
use App\Models\Foundation\WechatPayment;
use App\Services\Foundation\WechatPaymentService;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Illuminate\Support\Str;
use Yansongda\Artful\Exception\ContainerException;

/**
 * 红包发送任务
 *
 * 通过微信支付商家转账接口向指定用户发送现金红包。
 */
class SendRedpackJob extends BaseJob
{
    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(protected RedpackCode $code) {}

    /**
     * @throws InvalidArgumentException
     * @throws \Throwable
     * @throws ContainerException
     */
    public function handle(): void
    {
        $redpack = $this->code->redpack;

        if (!$redpack->isActive()) {
            return;
        }

        $user = $this->code->user;
        if (!$user) {
            return;
        }

        $openid = $this->getWechatOpenid($user);
        if (empty($openid)) {
            return;
        }

        $payment = $this->getWechatPayment($user);
        if (!$payment) {
            return;
        }

        $billNo = 'RP'.date('YmdHis').Str::random(6);

        $this->code->update([
            'bill_no' => $billNo,
            'status' => RedpackCodeStatus::Sending,
        ]);

        try {
            service(WechatPaymentService::class)->sendRedpack(
                payment: $payment,
                openid: $openid,
                amount: (int) ($this->code->amount * 100),
                billNo: $billNo,
                extra: [
                    'transfer_scene_id' => '1000',
                    'act_name' => $redpack->name,
                    'wishing' => '恭喜发财',
                    'transfer_remark' => $redpack->name,
                    'user_recv_perception' => '红包奖励',
                ],
            );

            $this->code->update(['status' => RedpackCodeStatus::Sent]);
        } catch (\Throwable $e) {
            $this->code->update(['status' => RedpackCodeStatus::Failed]);

            throw $e;
        }
    }

    /**
     * 获取用户的微信 openid
     */
    protected function getWechatOpenid($user): ?string
    {
        return $user->socialites()
            ->where('provider', SocialiteProvider::WeChat)
            ->value('provider_id');
    }

    /**
     * 获取当前租户的微信支付配置
     */
    protected function getWechatPayment($user): ?WechatPayment
    {
        $tenantId = $user->tenants()->first()?->getKey();

        if (!$tenantId) {
            return null;
        }

        return WechatPayment::ofEnabled()
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
