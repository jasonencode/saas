<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\RechargeOrderStatus;
use App\Enums\Finance\RechargeOrderType;
use App\Enums\Finance\WithdrawGateway;
use App\Enums\Finance\WithdrawOrderStatus;
use App\Models\Finance\RechargeOrder;
use App\Models\Finance\WithdrawOrder;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RechargeWithdrawApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // ─── POST /api/recharge ──────────────────────────────────────

    public function test_can_create_recharge_order(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/recharge', [
            'amount' => '100.00',
            'type' => RechargeOrderType::Balance->value,
            'gateway' => PaymentGateway::Wechat->value,
        ])->assertCreated()
            ->assertJsonPath('amount', '100.00')
            ->assertJsonPath('type', RechargeOrderType::Balance->value)
            ->assertJsonPath('gateway', PaymentGateway::Wechat->value)
            ->assertJsonPath('status', RechargeOrderStatus::Pending->value);

        $this->assertDatabaseHas('recharge_orders', [
            'user_id' => $this->user->id,
            'amount' => '100.00',
        ]);
    }

    public function test_recharge_requires_amount_type_and_gateway(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/recharge', [])
            ->assertUnprocessable()
            // BaseFormRequest 首错即停，只返回第一个错误字段
            ->assertJsonValidationErrors('amount');
    }

    public function test_recharge_rejects_amount_below_minimum(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/recharge', [
            'amount' => '0',
            'type' => RechargeOrderType::Balance->value,
            'gateway' => PaymentGateway::Wechat->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_recharge_requires_authentication(): void
    {
        $this->postJson('/api/recharge', [
            'amount' => '100.00',
            'type' => RechargeOrderType::Balance->value,
            'gateway' => PaymentGateway::Wechat->value,
        ])->assertUnauthorized();
    }

    // ─── GET /api/recharge ───────────────────────────────────────

    public function test_can_list_own_recharge_orders(): void
    {
        $this->makeRechargeOrder();
        $this->makeRechargeOrder();

        $other = User::factory()->create();
        RechargeOrder::create($this->rechargeAttributes($other));

        Sanctum::actingAs($this->user);

        $this->getJson('/api/recharge')
            ->assertOk()
            ->assertJsonStructure(['list', 'page' => ['current', 'total']]);

        $this->assertSame(2, $this->getJson('/api/recharge')->json('page.total'));
    }

    // ─── GET /api/recharge/{order} ───────────────────────────────

    public function test_can_show_own_recharge_order(): void
    {
        $order = $this->makeRechargeOrder();
        Sanctum::actingAs($this->user);

        $this->getJson('/api/recharge/'.$order->id)
            ->assertOk()
            ->assertJsonPath('order_id', $order->id);
    }

    public function test_cannot_show_another_users_recharge_order(): void
    {
        $other = User::factory()->create();
        $order = $this->makeRechargeOrder($other);
        Sanctum::actingAs($this->user);

        $this->getJson('/api/recharge/'.$order->id)
            ->assertForbidden();
    }

    // ─── GET /api/withdraw/balance ───────────────────────────────

    public function test_withdraw_balance_returns_account_summary(): void
    {
        Sanctum::actingAs($this->user);

        $this->user->account()->update([
            'balance' => '50.00',
            'frozen_balance' => '5.00',
        ]);

        $this->getJson('/api/withdraw/balance')
            ->assertOk()
            ->assertJsonPath('available_balance', '50.00')
            ->assertJsonPath('frozen_balance', '5.00');
    }

    // ─── POST /api/withdraw ──────────────────────────────────────

    public function test_can_create_alipay_withdraw_order(): void
    {
        Sanctum::actingAs($this->user);

        // 设置支付密码并给账户充值余额
        $this->user->account->update(['payment_password' => '520520', 'balance' => '100.00']);

        $this->postJson('/api/withdraw', [
            'amount' => '30.00',
            'gateway' => WithdrawGateway::Alipay->value,
            'account_info' => [
                'name' => '张三',
                'account' => 'alipay@example.com',
            ],
            'payment_password' => '520520',
        ])->assertCreated()
            ->assertJsonPath('amount', '30.00')
            ->assertJsonPath('status', WithdrawOrderStatus::Pending->value);

        // 创建提现冻结余额
        $this->assertSame('70.00', (string) $this->user->refresh()->account->balance);
        $this->assertSame('30.00', (string) $this->user->account->frozen_balance);
    }

    public function test_withdraw_rejects_wrong_payment_password(): void
    {
        Sanctum::actingAs($this->user);
        $this->user->account->update(['payment_password' => '520520', 'balance' => '100.00']);

        $this->postJson('/api/withdraw', [
            'amount' => '30.00',
            'gateway' => WithdrawGateway::Alipay->value,
            'account_info' => [
                'name' => '张三',
                'account' => 'alipay@example.com',
            ],
            'payment_password' => '102938',
        ])->assertStatus(400);

        // 余额未被冻结
        $this->assertSame('0.00', (string) $this->user->refresh()->account->frozen_balance);
    }

    public function test_withdraw_requires_account_info_for_alipay(): void
    {
        Sanctum::actingAs($this->user);
        $this->user->account->update(['payment_password' => '520520']);

        $this->postJson('/api/withdraw', [
            'amount' => '30.00',
            'gateway' => WithdrawGateway::Alipay->value,
            'payment_password' => '520520',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('account_info');
    }

    public function test_withdraw_rejects_insufficient_balance(): void
    {
        Sanctum::actingAs($this->user);
        $this->user->account->update(['payment_password' => '520520', 'balance' => '10.00']);

        $this->postJson('/api/withdraw', [
            'amount' => '30.00',
            'gateway' => WithdrawGateway::Alipay->value,
            'account_info' => [
                'name' => '张三',
                'account' => 'alipay@example.com',
            ],
            'payment_password' => '520520',
        ])->assertStatus(400);
    }

    public function test_withdraw_requires_authentication(): void
    {
        $this->postJson('/api/withdraw', [])->assertUnauthorized();
    }

    // ─── GET /api/withdraw ───────────────────────────────────────

    public function test_can_list_own_withdraw_orders(): void
    {
        $this->makeWithdrawOrder();
        $this->makeWithdrawOrder();

        $other = User::factory()->create();
        $this->makeWithdrawOrder($other);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/withdraw')
            ->assertOk()
            ->assertJsonStructure(['list', 'page' => ['current', 'total']]);

        $this->assertSame(2, $this->getJson('/api/withdraw')->json('page.total'));
    }

    public function test_withdraw_list_filters_by_status(): void
    {
        $this->makeWithdrawOrder(status: WithdrawOrderStatus::Pending);
        $this->makeWithdrawOrder(status: WithdrawOrderStatus::Completed);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/withdraw?status='.WithdrawOrderStatus::Completed->value)
            ->assertOk();

        $this->assertCount(1, $response->json('list'));
        $this->assertSame(
            WithdrawOrderStatus::Completed->value,
            $response->json('list.0.status'),
        );
    }

    // ─── GET /api/withdraw/{order} ───────────────────────────────

    public function test_can_show_own_withdraw_order(): void
    {
        $order = $this->makeWithdrawOrder();
        Sanctum::actingAs($this->user);

        $this->getJson('/api/withdraw/'.$order->id)
            ->assertOk()
            ->assertJsonPath('order_id', $order->id);
    }

    public function test_cannot_show_another_users_withdraw_order(): void
    {
        $other = User::factory()->create();
        $order = $this->makeWithdrawOrder($other);
        Sanctum::actingAs($this->user);

        $this->getJson('/api/withdraw/'.$order->id)
            ->assertForbidden();
    }

    // ─── POST /api/withdraw/{order}/cancel ───────────────────────

    public function test_can_cancel_pending_withdraw_order(): void
    {
        Sanctum::actingAs($this->user);
        $this->user->account->update(['payment_password' => '520520', 'balance' => '100.00']);

        $orderId = (int) $this->postJson('/api/withdraw', [
            'amount' => '30.00',
            'gateway' => WithdrawGateway::Alipay->value,
            'account_info' => [
                'name' => '张三',
                'account' => 'alipay@example.com',
            ],
            'payment_password' => '520520',
        ])->assertCreated()->json('order_id');

        $this->postJson('/api/withdraw/'.$orderId.'/cancel')
            ->assertOk()
            ->assertJsonPath('status', WithdrawOrderStatus::Cancelled->value);

        // 取消后解冻余额
        $this->assertSame('100.00', (string) $this->user->refresh()->account->balance);
        $this->assertSame('0.00', (string) $this->user->account->frozen_balance);
    }

    public function test_cannot_cancel_another_users_withdraw_order(): void
    {
        $other = User::factory()->create();
        $order = $this->makeWithdrawOrder($other);
        Sanctum::actingAs($this->user);

        $this->postJson('/api/withdraw/'.$order->id.'/cancel')
            ->assertForbidden();
    }

    // ─── 测试夹具 ────────────────────────────────────────────────

    private function rechargeAttributes(?User $user = null): array
    {
        return [
            'user_id' => ($user ?? $this->user)->id,
            'tenant_id' => null,
            'no' => 'R'.now()->format('YmdHis').random_int(1000, 9999),
            'amount' => '100.00',
            'received_amount' => '100.00',
            'type' => RechargeOrderType::Balance,
            'gateway' => PaymentGateway::Wechat,
            'status' => RechargeOrderStatus::Pending,
        ];
    }

    private function makeRechargeOrder(?User $user = null): RechargeOrder
    {
        return RechargeOrder::create($this->rechargeAttributes($user));
    }

    private function makeWithdrawOrder(?User $user = null, WithdrawOrderStatus $status = WithdrawOrderStatus::Pending): WithdrawOrder
    {
        $order = WithdrawOrder::create([
            'user_id' => ($user ?? $this->user)->id,
            'no' => 'W'.now()->format('YmdHis').random_int(1000, 9999),
            'amount' => '30.00',
            'fee' => '0.00',
            'actual_amount' => '30.00',
            'gateway' => WithdrawGateway::Alipay,
            'account_info' => [
                'name' => '张三',
                'account' => 'alipay@example.com',
            ],
        ]);

        // creating 钩子强制 status=pending，非 pending 状态在创建后写入
        if ($status !== WithdrawOrderStatus::Pending) {
            $order->update(['status' => $status]);
        }

        return $order;
    }
}
