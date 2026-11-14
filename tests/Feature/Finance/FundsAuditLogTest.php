<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\RechargeOrderType;
use App\Enums\Finance\WithdrawGateway;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\RechargeOrder;
use App\Models\Finance\UserAccount;
use App\Models\Finance\WithdrawOrder;
use App\Models\Foundation\WechatPayment;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Finance\PaymentService;
use App\Services\Finance\RechargeService;
use App\Services\Finance\WithdrawService;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * 资金链路审计：支付 / 充值 / 提现关键状态都写入日志中心
 */
class FundsAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_payment_and_recharge_flow_is_logged(): void
    {
        $recharge = $this->createRechargeOrder();
        $payment = $this->createPayment($recharge);

        service(PaymentService::class)->markPaidWithBusiness($payment, paymentNo: 'WX_AUDIT_001');

        $this->assertEventLogged('payment.paid', $payment);
        $this->assertEventLogged('recharge.paid', $recharge);
        $this->assertEventLogged('recharge.completed', $recharge);
    }

    public function test_payment_callback_is_logged(): void
    {
        $recharge = $this->createRechargeOrder();
        $payment = $this->createPayment($recharge);

        $this->createWechatPaymentConfig();
        $this->fakeWechatNotify($payment, [
            'trade_state' => 'SUCCESS',
            'transaction_id' => 'WX_NOTIFY_AUDIT',
        ]);

        $this->postJson("/api/payments/{$payment->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'SUCCESS']);

        $this->assertEventLogged('payment.notify_received', $payment);
        $this->assertEventLogged('payment.paid', $payment);
    }

    public function test_recharge_failure_is_logged(): void
    {
        $recharge = $this->createRechargeOrder();

        service(RechargeService::class)->markFailed($recharge);

        $this->assertEventLogged('recharge.failed', $recharge);
    }

    public function test_withdraw_lifecycle_is_logged(): void
    {
        $this->createAccount();

        $approved = $this->createWithdrawOrder();
        $this->assertEventLogged('withdraw.created', $approved);

        service(WithdrawService::class)->review($approved, true, 1, actor: $this->user);
        $this->assertEventLogged('withdraw.approved', $approved);

        service(WithdrawService::class)->complete($approved, 'PAY_AUDIT_001', $this->user);
        $this->assertEventLogged('withdraw.completed', $approved);

        $rejected = $this->createWithdrawOrder();
        service(WithdrawService::class)->review($rejected, false, 1, '信息不完整', $this->user);
        $this->assertEventLogged('withdraw.rejected', $rejected);

        $canceled = $this->createWithdrawOrder();
        service(WithdrawService::class)->cancel($canceled, $this->user);
        $this->assertEventLogged('withdraw.canceled', $canceled);
    }

    private function assertEventLogged(string $event, Model $subject): void
    {
        $this->assertDatabaseHas('event_logs', [
            'event' => $event,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
        ]);
    }

    private function createAccount(): UserAccount
    {
        // User 创建时会自动建账户，这里只补余额与支付密码
        $account = UserAccount::firstOrCreate(['user_id' => $this->user->id]);

        $account->update([
            'balance' => 100,
            'frozen_balance' => 0,
            'points' => 0,
            'frozen_points' => 0,
            'payment_password' => '123456',
        ]);

        return $account->fresh();
    }

    private function createRechargeOrder(): RechargeOrder
    {
        return service(RechargeService::class)->create(
            userId: $this->user->id,
            tenantId: $this->tenant->id,
            amount: 100,
            type: RechargeOrderType::Balance,
            gateway: PaymentGateway::Wechat,
        );
    }

    private function createWithdrawOrder(): WithdrawOrder
    {
        return service(WithdrawService::class)->create(
            userId: $this->user->id,
            amount: 10,
            gateway: WithdrawGateway::Wechat->value,
            accountInfo: ['name' => '测试', 'account' => 'wx_audit'],
            actor: $this->user,
        );
    }

    private function createPayment(RechargeOrder $paymentable): PaymentOrder
    {
        return PaymentOrder::create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'gateway' => PaymentGateway::Wechat,
            'amount' => $paymentable->getTotalAmount(),
            'expired_at' => now()->addMinutes(30),
            'paymentable' => $paymentable,
        ]);
    }

    /**
     * 创建微信支付配置（回调入口要求当前租户存在配置）
     */
    private function createWechatPaymentConfig(): WechatPayment
    {
        return WechatPayment::create([
            'tenant_id' => $this->tenant->id,
            'wechat_id' => 1,
            'name' => '测试商户',
            'mch_id' => 'mch_test',
            'secret' => 'secret_test',
        ]);
    }

    /**
     * 伪造微信回调返回数据（真实签名校验由 WechatPaymentService 负责）
     *
     * @param  array<string, mixed>  $data  回调数据（默认按支付单号生成）
     */
    private function fakeWechatNotify(PaymentOrder $payment, array $data): void
    {
        $this->mock(WechatPaymentService::class, function (MockInterface $mock) use ($payment, $data): void {
            $mock->shouldReceive('handleNotify')
                ->once()
                ->andReturn(array_merge(['out_trade_no' => $payment->no], $data));
        });
    }
}
