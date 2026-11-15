<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\LotteryPrizeStatus;
use App\Enums\Campaign\LotteryPrizeType;
use App\Enums\Foundation\SocialiteProvider;
use App\Jobs\Campaign\SendLotteryRedpackJob;
use App\Models\Campaign\Lottery;
use App\Models\Campaign\LotteryPrize;
use App\Models\Campaign\LotteryPrizeRecord;
use App\Models\Foundation\Socialite;
use App\Models\Foundation\SocialiteAccount;
use App\Models\Foundation\WechatPayment;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Campaign\LotteryService;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;
use Yansongda\Supports\Collection;

class LotteryRedpackFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private LotteryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LotteryService::class);
    }

    // ─── 抽奖分发 ─────────────────────────────────────────────────

    public function test_draw_dispatches_redpack_job_and_keeps_pending(): void
    {
        Queue::fake();

        $lottery = Lottery::factory()->create();
        LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Redpack,
            'prize_config' => ['amount' => 8.88],
            'weight' => 100,
            'total_quantity' => 10,
            'remaining_quantity' => 10,
        ]);
        $user = User::factory()->create();

        $draw = $this->service->draw($lottery, $user);

        // 红包奖品不再永远 Pending：抽中即分发打款任务（CAMP-06）
        $this->assertNotNull($draw->prizeRecord);
        $this->assertSame(LotteryPrizeStatus::Pending, $draw->prizeRecord->status);
        Queue::assertPushed(SendLotteryRedpackJob::class);
    }

    public function test_draw_without_amount_keeps_redpack_pending_without_job(): void
    {
        Queue::fake();

        $lottery = Lottery::factory()->create();
        LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Redpack,
            'prize_config' => [],
            'weight' => 100,
            'total_quantity' => 10,
            'remaining_quantity' => 10,
        ]);
        $user = User::factory()->create();

        $draw = $this->service->draw($lottery, $user);

        // 金额未配置：保留待兑奖，不分发任务，供人工处理
        $this->assertSame(LotteryPrizeStatus::Pending, $draw->prizeRecord->status);
        Queue::assertNotPushed(SendLotteryRedpackJob::class);
    }

    // ─── 后台兑奖分发 ─────────────────────────────────────────────

    public function test_fulfill_prize_dispatches_redpack_job(): void
    {
        Queue::fake();

        $record = $this->redpackRecord(['amount' => 6.66]);

        $this->service->fulfillPrize($record, '补发');

        $this->assertSame(LotteryPrizeStatus::Pending, $record->fresh()->status);
        Queue::assertPushed(SendLotteryRedpackJob::class);
    }

    public function test_fulfill_prize_rejects_redpack_without_amount(): void
    {
        Queue::fake();

        $record = $this->redpackRecord([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('奖品金额未配置');

        $this->service->fulfillPrize($record);

        Queue::assertNotPushed(SendLotteryRedpackJob::class);
    }

    // ─── 打款任务 ─────────────────────────────────────────────────

    public function test_job_transfers_and_marks_fulfilled(): void
    {
        [$record, $payment] = $this->fixtureWithWechatUser(['amount' => 8.88]);

        $this->mock(WechatPaymentService::class, function (MockInterface $mock) use ($payment): void {
            $mock->shouldReceive('sendRedpack')
                ->once()
                ->withArgs(fn ($paymentArg, $openid, $amount, $billNo): bool => $paymentArg->is($payment)
                    && $openid === 'openid_test'
                    && $amount === 888
                    && str_starts_with($billNo, 'LP'))
                ->andReturn(new Collection(['out_bill_no' => 'LP-TEST']));
        });

        (new SendLotteryRedpackJob($record))->handle();

        $record->refresh();

        $this->assertSame(LotteryPrizeStatus::Fulfilled, $record->status);
        $this->assertSame('微信转账发放', $record->fulfillment_note);
        $this->assertNotNull($record->bill_no);
    }

    public function test_job_reuses_bill_no_on_retry(): void
    {
        [$record, $payment] = $this->fixtureWithWechatUser(['amount' => 8.88]);
        $record->update(['bill_no' => 'LP-EXISTING']);

        $calls = 0;

        $this->mock(WechatPaymentService::class, function (MockInterface $mock) use (&$calls): void {
            $mock->shouldReceive('sendRedpack')
                ->twice()
                ->withArgs(fn ($payment, $openid, $amount, $billNo): bool => $billNo === 'LP-EXISTING')
                ->andReturnUsing(function () use (&$calls): Collection {
                    $calls++;

                    // 首次「已受理但超时」，重试仍按同一单号打款（渠道去重）
                    throw new RuntimeException('transfer timeout');
                });
        });

        try {
            (new SendLotteryRedpackJob($record))->handle();
        } catch (RuntimeException) {
            // 通道异常向上抛，交由队列重试
        }

        $this->assertSame(1, $calls);
        $this->assertSame(LotteryPrizeStatus::Pending, $record->fresh()->status);

        try {
            (new SendLotteryRedpackJob($record->fresh()))->handle();
        } catch (RuntimeException) {
            // 同上
        }

        $this->assertSame(2, $calls);
        $this->assertSame('LP-EXISTING', $record->fresh()->bill_no);
    }

    public function test_job_skips_non_pending_record(): void
    {
        [$record] = $this->fixtureWithWechatUser(['amount' => 8.88]);
        $record->update(['status' => LotteryPrizeStatus::Fulfilled]);

        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendRedpack');
        });

        (new SendLotteryRedpackJob($record))->handle();

        $this->assertSame(LotteryPrizeStatus::Fulfilled, $record->fresh()->status);
    }

    public function test_job_marks_failure_when_user_has_no_wechat_binding(): void
    {
        [$record] = $this->fixtureWithWechatUser(['amount' => 8.88], bindWechat: false);

        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendRedpack');
        });

        (new SendLotteryRedpackJob($record))->handle();

        $record->refresh();

        // 可预期失败：记录原因并保留待兑奖，供人工处理（CAMP-06）
        $this->assertSame(LotteryPrizeStatus::Pending, $record->status);
        $this->assertSame('发放失败：用户未绑定微信，无法发放', $record->fulfillment_note);
    }

    public function test_job_marks_failure_when_no_payment_config(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $this->bindWechat($tenant, $user);

        $record = $this->redpackRecord(['amount' => 8.88], $tenant, $user);

        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendRedpack');
        });

        (new SendLotteryRedpackJob($record))->handle();

        $record->refresh();

        $this->assertSame(LotteryPrizeStatus::Pending, $record->status);
        $this->assertSame('发放失败：未配置可用的微信支付商户', $record->fulfillment_note);
    }

    // ─── 测试夹具 ────────────────────────────────────────────────

    /**
     * @return array{0: LotteryPrizeRecord, 1: WechatPayment}
     */
    private function fixtureWithWechatUser(array $prizeDetail, bool $bindWechat = true): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        if ($bindWechat) {
            $this->bindWechat($tenant, $user);
        }

        $payment = WechatPayment::create([
            'tenant_id' => $tenant->getKey(),
            'wechat_id' => 1,
            'name' => '测试商户',
            'mch_id' => 'mch_test',
            'secret' => 'secret_test',
            'status' => true,
        ]);

        return [$this->redpackRecord($prizeDetail, $tenant, $user), $payment];
    }

    private function redpackRecord(array $prizeDetail, ?Tenant $tenant = null, ?User $user = null): LotteryPrizeRecord
    {
        $tenant ??= Tenant::factory()->create();
        $user ??= User::factory()->create();

        $lottery = Lottery::factory()->create(['tenant_id' => $tenant->getKey()]);
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Redpack,
            'prize_config' => $prizeDetail,
        ]);
        $draw = $lottery->draws()->create([
            'user_id' => $user->getKey(),
            'lottery_prize_id' => $prize->getKey(),
            'draw_cost_type' => 'free',
            'draw_cost_amount' => 0,
        ]);

        return $draw->prizeRecord()->create([
            'lottery_id' => $lottery->id,
            'user_id' => $user->getKey(),
            'lottery_prize_id' => $prize->getKey(),
            'type' => LotteryPrizeType::Redpack,
            'prize_detail' => $prizeDetail,
            'status' => LotteryPrizeStatus::Pending,
        ]);
    }

    private function bindWechat(Tenant $tenant, User $user): Socialite
    {
        $account = SocialiteAccount::create([
            'tenant_id' => $tenant->getKey(),
            'provider' => SocialiteProvider::WeChat,
            'name' => '测试公众号',
            'app_key' => 'app_key_test',
            'app_secret' => 'app_secret_test',
        ]);

        return $user->socialites()->create([
            'tenant_id' => $tenant->getKey(),
            'account_id' => $account->getKey(),
            'provider_id' => 'openid_test',
        ]);
    }
}
