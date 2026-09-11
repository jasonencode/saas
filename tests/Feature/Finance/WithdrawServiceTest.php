<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\WithdrawGateway;
use App\Enums\Finance\WithdrawOrderStatus;
use App\Models\Finance\UserAccount;
use App\Models\Finance\WithdrawOrder;
use App\Models\User\User;
use App\Services\Finance\WithdrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class WithdrawServiceTest extends TestCase
{
    use RefreshDatabase;

    private WithdrawService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WithdrawService::class);
    }

    // ─── create ───────────────────────────────────────────────────

    public function test_create_withdraw_order_successfully(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');

        $order = $this->service->create(
            userId: $account->user_id,
            amount: 200,
            gateway: WithdrawGateway::Wechat->value,
            accountInfo: ['name' => '张三', 'account' => 'wx_123'],
            ip: '127.0.0.1',
            userAgent: 'TestAgent',
        );

        $this->assertInstanceOf(WithdrawOrder::class, $order);
        $this->assertEquals(200, $order->amount);
        $this->assertEquals(0, $order->fee);
        $this->assertEquals(200, $order->actual_amount);
        $this->assertEquals(WithdrawOrderStatus::Pending, $order->status);
        $this->assertEquals('127.0.0.1', $order->ip);
        $this->assertEquals('TestAgent', $order->user_agent);
    }

    public function test_create_freezes_balance(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');

        $this->service->create(
            userId: $account->user_id,
            amount: 300,
            gateway: WithdrawGateway::Alipay->value,
            accountInfo: ['name' => '李四', 'account' => 'ali_456'],
        );

        $account->refresh();
        $this->assertEquals(700, $account->balance);
        $this->assertEquals(300, $account->frozen_balance);
    }

    public function test_create_with_fee(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');

        $order = $this->service->create(
            userId: $account->user_id,
            amount: 200,
            gateway: WithdrawGateway::Bank->value,
            accountInfo: ['name' => '王五', 'account' => '6222', 'bank' => '招商银行'],
            fee: 5,
        );

        $this->assertEquals(195.0, (float) $order->actual_amount);
    }

    public function test_create_throws_when_amount_is_zero(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('提现金额必须大于 0');

        $this->service->create(
            userId: $account->user_id,
            amount: 0,
            gateway: WithdrawGateway::Wechat->value,
            accountInfo: ['name' => 'test', 'account' => '123'],
        );
    }

    public function test_create_throws_when_no_account(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('用户账户不存在');

        $this->service->create(
            userId: 99999,
            amount: 100,
            gateway: WithdrawGateway::Wechat->value,
            accountInfo: ['name' => 'test', 'account' => '123'],
        );
    }

    public function test_create_throws_when_no_payment_password(): void
    {
        $account = $this->createAccount(balance: 1000);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('请先设置支付密码');

        $this->service->create(
            userId: $account->user_id,
            amount: 100,
            gateway: WithdrawGateway::Wechat->value,
            accountInfo: ['name' => 'test', 'account' => '123'],
        );
    }

    public function test_create_throws_when_insufficient_balance(): void
    {
        $account = $this->createAccount(balance: 50, payment_password: '123456');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('余额不足');

        $this->service->create(
            userId: $account->user_id,
            amount: 100,
            gateway: WithdrawGateway::Wechat->value,
            accountInfo: ['name' => 'test', 'account' => '123'],
        );
    }

    public function test_create_throws_when_fee_exceeds_amount(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('实际到账金额不能小于 0.01');

        $this->service->create(
            userId: $account->user_id,
            amount: 10,
            gateway: WithdrawGateway::Wechat->value,
            accountInfo: ['name' => 'test', 'account' => '123'],
            fee: 10,
        );
    }

    public function test_create_records_account_log(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');

        $this->service->create(
            userId: $account->user_id,
            amount: 200,
            gateway: WithdrawGateway::Wechat->value,
            accountInfo: ['name' => 'test', 'account' => '123'],
        );

        $this->assertDatabaseHas('user_account_logs', [
            'user_id' => $account->user_id,
            'amount' => -200,
            'remark' => '提现冻结',
        ]);
    }

    public function test_create_throws_when_balance_is_frozen_by_previous_order(): void
    {
        $account = $this->createAccount(balance: 500, payment_password: '123456');

        $this->createWithdrawOrder($account, 300);

        // 余额已被上一笔提现冻结，第二次提现应受余额校验拦截
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('余额不足');

        $this->createWithdrawOrder($account, 300);
    }

    // ─── review (approve) ─────────────────────────────────────────

    public function test_approve_sets_status_to_approved(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 500);

        $this->service->review($order, true, 1);

        $order->refresh();
        $this->assertEquals(WithdrawOrderStatus::Approved, $order->status);
        $this->assertEquals(1, $order->reviewer_id);
        $this->assertNotNull($order->reviewed_at);
    }

    public function test_approve_keeps_frozen_balance(): void
    {
        $account = $this->createAccount(balance: 500, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 300);

        $this->service->review($order, true, 1);

        $account->refresh();
        $this->assertEquals(200, $account->balance);
        $this->assertEquals(300, $account->frozen_balance);
    }

    public function test_review_after_approved_throws(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 500);
        $this->service->review($order, true, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('提现订单状态不正确');

        $this->service->review($order, true, 2);
    }

    // ─── review (reject) ──────────────────────────────────────────

    public function test_reject_sets_status_to_rejected(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 500);

        $this->service->review($order, false, 1, '信息不完整');

        $order->refresh();
        $this->assertEquals(WithdrawOrderStatus::Rejected, $order->status);
        $this->assertEquals('信息不完整', $order->reject_reason);
    }

    public function test_reject_unfreezes_balance(): void
    {
        $account = $this->createAccount(balance: 500, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 300);

        $this->service->review($order, false, 1, '拒绝');

        $account->refresh();
        $this->assertEquals(500, $account->balance);
        $this->assertEquals(0, $account->frozen_balance);
    }

    public function test_reject_twice_does_not_unfreeze_again(): void
    {
        $account = $this->createAccount(balance: 500, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 300);
        $this->service->review($order, false, 1, '拒绝');

        try {
            $this->service->review($order, false, 1, '重复拒绝');
        } catch (InvalidArgumentException) {
            // 预期：第二次审核被状态校验拒绝
        }

        $account->refresh();
        $this->assertEquals(500, $account->balance);
        $this->assertEquals(0, $account->frozen_balance);
    }

    public function test_review_throws_when_status_not_pending(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 500);
        $order->update(['status' => WithdrawOrderStatus::Approved]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('提现订单状态不正确');

        $this->service->review($order, true, 1);
    }

    // ─── complete ─────────────────────────────────────────────────

    public function test_complete_sets_status_to_completed(): void
    {
        $account = $this->createAccount(balance: 500, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 300);
        $this->service->review($order, true, 1);

        $this->service->complete($order, 'PAY_20240101_001');

        $order->refresh();
        $this->assertEquals(WithdrawOrderStatus::Completed, $order->status);
        $this->assertEquals('PAY_20240101_001', $order->payment_no);
        $this->assertNotNull($order->paid_at);
    }

    public function test_complete_deducts_frozen_balance(): void
    {
        $account = $this->createAccount(balance: 500, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 300);
        $this->service->review($order, true, 1);

        $this->service->complete($order, 'PAY_001');

        $account->refresh();
        $this->assertEquals(200, $account->balance);
        $this->assertEquals(0, $account->frozen_balance);
    }

    public function test_complete_twice_does_not_deduct_again(): void
    {
        $account = $this->createAccount(balance: 500, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 300);
        $this->service->review($order, true, 1);
        $this->service->complete($order, 'PAY_001');

        try {
            $this->service->complete($order, 'PAY_002');
        } catch (InvalidArgumentException) {
            // 预期：重复打款被状态校验拒绝
        }

        $account->refresh();
        $this->assertEquals(200, $account->balance);
        $this->assertEquals(0, $account->frozen_balance);
    }

    public function test_complete_throws_when_status_not_approved(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 500);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('提现订单状态不正确');

        $this->service->complete($order, 'PAY_001');
    }

    // ─── cancel ───────────────────────────────────────────────────

    public function test_cancel_sets_status_to_cancelled(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 500);

        $this->service->cancel($order);

        $order->refresh();
        $this->assertEquals(WithdrawOrderStatus::Cancelled, $order->status);
    }

    public function test_cancel_unfreezes_balance(): void
    {
        $account = $this->createAccount(balance: 500, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 300);

        $this->service->cancel($order);

        $account->refresh();
        $this->assertEquals(500, $account->balance);
        $this->assertEquals(0, $account->frozen_balance);
    }

    public function test_cancel_after_approved_throws(): void
    {
        $account = $this->createAccount(balance: 500, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 300);
        $this->service->review($order, true, 1);

        try {
            $this->service->cancel($order);
        } catch (InvalidArgumentException) {
            // 预期：审核通过后不可取消
        }

        $order->refresh();
        $account->refresh();
        $this->assertEquals(WithdrawOrderStatus::Approved, $order->status);
        $this->assertEquals(300, $account->frozen_balance);
    }

    public function test_cancel_throws_when_status_not_pending(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');
        $order = $this->createWithdrawOrder($account, 500);
        $order->update(['status' => WithdrawOrderStatus::Approved]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('提现订单状态不可取消');

        $this->service->cancel($order);
    }

    // ─── full lifecycle ───────────────────────────────────────────

    public function test_full_lifecycle_create_approve_complete(): void
    {
        $account = $this->createAccount(balance: 1000, payment_password: '123456');

        // 创建（冻结金额）
        $order = $this->createWithdrawOrder($account, 500);
        $this->assertEquals(WithdrawOrderStatus::Pending, $order->status);
        $account->refresh();
        $this->assertEquals(500, $account->balance);
        $this->assertEquals(500, $account->frozen_balance);

        // 审核通过
        $this->service->review($order, true, 1);
        $order->refresh();
        $this->assertEquals(WithdrawOrderStatus::Approved, $order->status);

        // 打款完成
        $this->service->complete($order, 'PAY_FINAL');
        $order->refresh();
        $this->assertEquals(WithdrawOrderStatus::Completed, $order->status);

        // 验证余额最终状态
        $account->refresh();
        $this->assertEquals(500, $account->balance);
        $this->assertEquals(0, $account->frozen_balance);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function createAccount(
        float $balance = 0,
        float $frozen_balance = 0,
        ?string $payment_password = null,
    ): UserAccount {
        $user = User::factory()->create();

        $account = $user->account ?? UserAccount::create(['user_id' => $user->id]);
        $account->update([
            'balance' => $balance,
            'frozen_balance' => $frozen_balance,
            'payment_password' => $payment_password,
        ]);

        return $account->fresh();
    }

    /**
     * 通过服务创建提现单（会同步冻结账户金额）
     */
    private function createWithdrawOrder(UserAccount $account, float $amount): WithdrawOrder
    {
        return $this->service->create(
            userId: $account->user_id,
            amount: $amount,
            gateway: WithdrawGateway::Wechat->value,
            accountInfo: ['name' => '测试', 'account' => 'test'],
        );
    }
}
