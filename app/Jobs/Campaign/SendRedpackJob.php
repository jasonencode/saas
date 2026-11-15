<?php

namespace App\Jobs\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Enums\Foundation\SocialiteProvider;
use App\Jobs\BaseJob;
use App\Models\Campaign\RedpackCode;
use App\Models\Foundation\WechatPayment;
use App\Services\Foundation\WechatPaymentService;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yansongda\Artful\Exception\ContainerException;

/**
 * 红包发送任务
 *
 * 通过微信支付商家转账接口向指定用户发送现金红包。
 */
#[Timeout(60)]
#[Tries(3)]
class SendRedpackJob extends BaseJob
{
    public function __construct(protected RedpackCode $code) {}

    /**
     * @throws InvalidArgumentException
     * @throws \Throwable
     * @throws ContainerException
     */
    public function handle(): void
    {
        // 幂等：已发放/已禁用的红包码不再重复打款
        if ($this->code->status === RedpackCodeStatus::Sent) {
            return;
        }

        if ($this->code->status === RedpackCodeStatus::Disabled) {
            $this->markFailed('红包码已禁用');

            return;
        }

        $redpack = $this->code->redpack;

        if (!$redpack->isActive()) {
            $this->markFailed('红包活动不在进行中');

            return;
        }

        $user = $this->code->user;
        if (!$user) {
            $this->markFailed('领取用户不存在');

            return;
        }

        $openid = $this->getWechatOpenid($user);
        if (empty($openid)) {
            $this->markFailed('用户未绑定微信，无法发放');

            return;
        }

        $payment = $this->getWechatPayment((int) $redpack->tenant_id);
        if (!$payment) {
            $this->markFailed('未配置可用的微信支付商户');

            return;
        }

        // 复用已生成的商户单号：队列重试时渠道按 out_bill_no 去重，避免重复打款
        $billNo = $this->code->bill_no ?: 'RP'.date('YmdHis').Str::random(6);

        $this->code->update([
            'bill_no' => $billNo,
            'status' => RedpackCodeStatus::Sending,
        ]);

        try {
            service(WechatPaymentService::class)->sendRedpack(
                payment: $payment,
                openid: $openid,
                amount: (int) bcmul((string) $this->code->amount, '100', 0),
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
     * 静默失败分支：置为发放失败并记录原因，避免钱未出、单仍停在已领取
     */
    protected function markFailed(string $reason): void
    {
        $this->code->update(['status' => RedpackCodeStatus::Failed]);

        Log::warning('红包发放失败', [
            'redpack_code_id' => $this->code->getKey(),
            'tenant_id' => $this->code->redpack?->tenant_id,
            'reason' => $reason,
        ]);
    }

    /**
     * 获取用户的微信 openid
     *
     * provider 存放在 socialite_accounts 上，经 account 关联过滤（socialites 表无 provider 列）。
     */
    protected function getWechatOpenid($user): ?string
    {
        return $user->socialites()
            ->whereHas('account', fn (Builder $query) => $query->where('provider', SocialiteProvider::WeChat))
            ->value('provider_id');
    }

    /**
     * 获取活动所属租户的微信支付配置
     */
    protected function getWechatPayment(int $tenantId): ?WechatPayment
    {
        if (!$tenantId) {
            return null;
        }

        return WechatPayment::ofEnabled()
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
