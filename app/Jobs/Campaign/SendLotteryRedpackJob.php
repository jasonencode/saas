<?php

namespace App\Jobs\Campaign;

use App\Enums\Campaign\LotteryPrizeStatus;
use App\Enums\Foundation\SocialiteProvider;
use App\Jobs\BaseJob;
use App\Models\Campaign\LotteryPrizeRecord;
use App\Models\Foundation\WechatPayment;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * 抽奖红包奖品发放任务
 *
 * 中奖红包奖品经微信商家转账打款：复用商户单号保证重试幂等，
 * 可预期失败（未绑定微信 / 无商户配置）记录原因并保留待兑奖，供人工处理。
 */
#[Timeout(60)]
#[Tries(3)]
class SendLotteryRedpackJob extends BaseJob
{
    public function __construct(protected LotteryPrizeRecord $record) {}

    /**
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function handle(): void
    {
        // 幂等：仅待兑奖状态处理（已兑奖 / 已取消 / 已过期不重复打款）
        if ($this->record->status !== LotteryPrizeStatus::Pending) {
            return;
        }

        try {
            $this->send();
        } catch (InvalidArgumentException $exception) {
            // 可预期失败不消耗重试：记录原因保留待兑奖，供运营人工处理（CAMP-06）
            $this->record->update([
                'fulfillment_note' => '发放失败：'.$exception->getMessage(),
            ]);

            Log::warning('抽奖红包发放失败', [
                'lottery_prize_record_id' => $this->record->getKey(),
                'reason' => $exception->getMessage(),
            ]);

            return;
        }
    }

    /**
     * 通过微信商家转账发放红包奖品
     *
     * @throws InvalidArgumentException 奖品配置、用户或商户不可用
     * @throws \Throwable 通道异常（交由队列重试）
     */
    protected function send(): void
    {
        $amount = (float) (($this->record->prize_detail ?? [])['amount'] ?? 0);

        if ($amount <= 0) {
            throw new InvalidArgumentException('奖品金额未配置');
        }

        $user = $this->record->user;

        if (!$user) {
            throw new InvalidArgumentException('中奖用户不存在');
        }

        $openid = $user->socialites()
            ->whereHas('account', fn (Builder $query) => $query->where('provider', SocialiteProvider::WeChat))
            ->value('provider_id');

        if (empty($openid)) {
            throw new InvalidArgumentException('用户未绑定微信，无法发放');
        }

        $payment = $this->paymentForTenant((int) ($this->record->lottery?->tenant_id ?? 0));

        if (!$payment) {
            throw new InvalidArgumentException('未配置可用的微信支付商户');
        }

        // 复用已生成的商户单号：队列重试时渠道按 out_bill_no 去重，避免重复打款
        $billNo = $this->record->bill_no ?: 'LP'.date('YmdHis').Str::random(6);

        $this->record->update(['bill_no' => $billNo]);

        service(WechatPaymentService::class)->sendRedpack(
            payment: $payment,
            openid: $openid,
            amount: (int) bcmul((string) $amount, '100', 0),
            billNo: $billNo,
            extra: [
                'transfer_scene_id' => '1000',
                'act_name' => $this->record->lottery?->name ?? '抽奖活动',
                'wishing' => '恭喜发财',
                'transfer_remark' => '抽奖红包奖励',
                'user_recv_perception' => '红包奖励',
            ],
        );

        $this->record->update([
            'status' => LotteryPrizeStatus::Fulfilled,
            'fulfillment_note' => '微信转账发放',
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * 获取活动所属租户的微信支付配置
     */
    protected function paymentForTenant(int $tenantId): ?WechatPayment
    {
        if (!$tenantId) {
            return null;
        }

        return WechatPayment::ofEnabled()
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
