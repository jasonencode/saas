<?php

namespace App\Jobs\Campaign;

use App\Jobs\BaseJob;
use App\Models\Campaign\Redpack;
use App\Models\Foundation\WechatPayment;
use App\Models\User\User;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 红包发送任务
 *
 * 通过微信支付接口向指定用户发送现金红包。
 */
class SendRedpackJob extends BaseJob
{
    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(
        protected Redpack $redpack,
        protected User $user,
        protected float $amount,
    ) {}

    public function handle(): void
    {
        if (!$this->redpack->isActive()) {
            Log::warning('[SendRedpack] 红包活动未激活', ['redpack_id' => $this->redpack->id]);

            return;
        }

        // TODO: 从用户关联的微信账号获取 openid
        $openid = '';
        if (empty($openid)) {
            Log::warning('[SendRedpack] 用户无关联微信 openid', ['user_id' => $this->user->id]);

            return;
        }

        // TODO: 根据实际业务获取对应的 WechatPayment（当前取租户下第一个可用的）
        $payment = WechatPayment::ofEnabled()->first();
        if (!$payment) {
            Log::warning('[SendRedpack] 无可用微信支付配置');

            return;
        }

        $billNo = 'RP'.date('YmdHis').Str::random(6);

        service(WechatPaymentService::class)->sendRedpack(
            payment: $payment,
            openid: $openid,
            amount: (int) ($this->amount * 100),
            billNo: $billNo,
            extra: [
                'send_name' => $this->redpack->name,
                'wishing' => '恭喜发财',
                'act_name' => $this->redpack->name,
                'remark' => $this->redpack->description ?? '',
            ],
        );
    }
}
