<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\RechargeOrderStatus;
use App\Enums\Finance\RechargeOrderType;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Finance\RechargeOrder;
use App\Models\Finance\UserAccount;
use App\Models\Foundation\WechatPayment;
use App\Models\Mall\Order;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Finance\PaymentableResolver;
use App\Services\Finance\PaymentRefundService;
use App\Services\Finance\PaymentService;
use App\Services\Finance\RechargeService;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;
use Yansongda\Supports\Collection;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant);

        $this->withHeader('X-Tenant-Id', (string) $this->tenant->getKey());
    }

    public function test_store_creates_payment_for_own_order_and_locks_tenant(): void
    {
        Sanctum::actingAs($this->user);
        $order = $this->createOrder();

        $this->postJson('/api/payments', [
            'gateway' => PaymentGateway::Balance->value,
            'paymentable_type' => 'order',
            'paymentable_id' => $order->getKey(),
        ])->assertCreated()
            ->assertJsonPath('paymentable_type', 'order');

        $this->assertDatabaseHas('payment_orders', [
            'user_id' => $this->user->getKey(),
            'tenant_id' => $this->tenant->getKey(),
            'paymentable_id' => $order->getKey(),
            'amount' => '100.00',
        ]);
    }

    public function test_store_rejects_another_users_order(): void
    {
        $other = User::factory()->create();
        $order = $this->createOrder($other);
        Sanctum::actingAs($this->user);

        $this->postJson('/api/payments', [
            'gateway' => PaymentGateway::Balance->value,
            'paymentable_type' => 'order',
            'paymentable_id' => $order->getKey(),
        ])->assertNotFound();
    }

    public function test_store_reuses_pending_payment(): void
    {
        Sanctum::actingAs($this->user);
        $order = $this->createOrder();

        $first = $this->postJson('/api/payments', [
            'gateway' => PaymentGateway::Balance->value,
            'paymentable_type' => 'order',
            'paymentable_id' => $order->getKey(),
        ])->assertCreated()->json('order_id');

        $second = $this->postJson('/api/payments', [
            'gateway' => PaymentGateway::Balance->value,
            'paymentable_type' => 'order',
            'paymentable_id' => $order->getKey(),
        ])->assertOk()->json('order_id');

        $this->assertSame($first, $second);
        $this->assertSame(1, PaymentOrder::query()->count());
    }

    public function test_store_rejects_payment_without_business_subject(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/payments', [
            'gateway' => PaymentGateway::Balance->value,
            'amount' => '1.00',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['paymentable_type']);

        $this->assertSame(0, PaymentOrder::query()->count());
    }

    public function test_store_rejects_payment_without_paymentable_id(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/payments', [
            'gateway' => PaymentGateway::Balance->value,
            'paymentable_type' => 'order',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['paymentable_id']);

        $this->assertSame(0, PaymentOrder::query()->count());
    }

    public function test_store_rejects_unsupported_paymentable_type(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/payments', [
            'gateway' => PaymentGateway::Balance->value,
            'paymentable_type' => 'user',
            'paymentable_id' => $this->user->getKey(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['paymentable_type']);

        $this->assertSame(0, PaymentOrder::query()->count());
    }

    public function test_notify_rejects_amount_mismatch(): void
    {
        $this->createWechatPaymentConfig();
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentGateway::Wechat);

        $this->fakeWechatNotify($payment, [
            'total' => 10001,
        ]);

        $this->postJson("/api/payments/{$payment->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'FAIL']);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertDatabaseHas('event_logs', [
            'event' => 'payment.notify_failed',
            'subject_id' => $payment->getKey(),
        ]);
    }

    public function test_notify_rejects_non_wechat_payment(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentGateway::Balance);

        $this->postJson("/api/payments/{$payment->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'FAIL']);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_notify_stores_transaction_and_paid_amount(): void
    {
        $this->createWechatPaymentConfig();
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentGateway::Wechat);

        $this->fakeWechatNotify($payment, [
            'total' => 10000,
            'transaction_id' => 'WX_TX_001',
        ]);

        $this->postJson("/api/payments/{$payment->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'SUCCESS']);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('WX_TX_001', $payment->transaction_id);
        $this->assertSame('100.00', $payment->paid_amount);
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_notify_creates_automatic_refund_when_paymentable_deleted(): void
    {
        $this->createWechatPaymentConfig();
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentGateway::Wechat);

        $order->delete();

        $this->fakeWechatNotify($payment, [
            'total' => 10000,
            'transaction_id' => 'WX_TX_DELETED',
        ], refund: true);

        $this->postJson("/api/payments/{$payment->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'SUCCESS']);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Refunded, $payment->status);

        $refund = PaymentRefund::query()->where('payment_order_id', $payment->getKey())->firstOrFail();
        $this->assertSame(PaymentRefundStatus::Completed, $refund->status);
    }

    public function test_payment_password_locks_after_too_many_failures(): void
    {
        $account = UserAccount::firstOrCreate(['user_id' => $this->user->getKey()]);
        $account->update(['balance' => 200, 'payment_password' => '246813']);
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentGateway::Balance);

        for ($i = 0; $i < 5; $i++) {
            try {
                service(PaymentService::class)->payByBalance($payment, '999999', $this->user);
                $this->fail('错误密码不应通过');
            } catch (InvalidArgumentException) {
                // 预期：累计失败次数
            }
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('支付密码错误次数过多');

        service(PaymentService::class)->payByBalance($payment, '246813', $this->user);
    }

    public function test_refundable_amount_includes_soft_deleted_refunds(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentGateway::Wechat, paid: true);

        $refund = PaymentRefund::create([
            'tenant_id' => $this->tenant->getKey(),
            'payment_order_id' => $payment->getKey(),
            'amount' => '60.00',
            'status' => PaymentRefundStatus::Completed,
        ]);
        $refund->delete();

        $this->assertSame('40.00', service(PaymentRefundService::class)->refundableAmount($payment));
    }

    public function test_expire_command_cancels_expired_payments(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentGateway::Balance);
        $payment->update(['expired_at' => now()->subMinute()]);

        $this->artisan('app:finance:expire-payment-orders')->assertSuccessful();

        $this->assertSame(PaymentStatus::Canceled, $payment->fresh()->status);
    }

    public function test_recharge_double_payment_credits_once_and_refunds_second(): void
    {
        $this->createWechatPaymentConfig();
        $this->mockWechatRefundSuccess();

        $recharge = service(RechargeService::class)->create(
            userId: $this->user->getKey(),
            tenantId: $this->tenant->getKey(),
            amount: 100,
            type: RechargeOrderType::Balance,
            gateway: PaymentGateway::Wechat,
        );

        $first = $this->createPayment($recharge, PaymentGateway::Wechat);
        $second = $this->createPayment($recharge, PaymentGateway::Wechat);

        service(PaymentService::class)->markPaidWithBusiness($first, paymentNo: 'WX_R_1');
        service(PaymentService::class)->markPaidWithBusiness($second, paymentNo: 'WX_R_2');

        $this->assertSame(RechargeOrderStatus::Completed, $recharge->fresh()->status);
        $this->assertSame('100.00', (string) UserAccount::find($this->user->getKey())->balance);
        $this->assertSame(1, UserAccount::find($this->user->getKey())->logs()->count());

        $this->assertSame(PaymentStatus::Paid, $first->fresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $second->fresh()->status);
        $this->assertDatabaseHas('payment_refunds', [
            'payment_order_id' => $second->getKey(),
            'status' => PaymentRefundStatus::Completed->value,
        ]);
    }

    public function test_paymentable_resolver_returns_order_key(): void
    {
        $this->assertSame('order', PaymentableResolver::keyFor(Order::class));
        $this->assertSame('recharge', PaymentableResolver::keyFor(RechargeOrder::class));
    }

    public function test_show_denies_when_payment_user_is_missing(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentGateway::Balance);
        $this->user->delete();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $this->getJson("/api/payments/{$payment->id}")->assertNotFound();
    }

    private function createOrder(?User $user = null): Order
    {
        $user ??= $this->user;

        return Order::create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $user->getKey(),
            'status' => OrderStatus::Pending,
            'fulfillment_type' => FulfillmentType::Virtual,
            'amount' => '100.00',
            'freight' => '0.00',
            'coupon_discount' => '0.00',
        ]);
    }

    private function createPayment(
        Order|RechargeOrder $paymentable,
        PaymentGateway $gateway,
        bool $paid = false,
    ): PaymentOrder {
        $payment = PaymentOrder::create([
            'user_id' => $paymentable->user_id,
            'tenant_id' => $paymentable->tenant_id,
            'gateway' => $gateway,
            'amount' => $paymentable->getTotalAmount(),
            'expired_at' => now()->addMinutes(30),
            'paymentable' => $paymentable,
        ]);

        if ($paid) {
            $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'paid_amount' => $payment->amount]);
        }

        return $payment;
    }

    private function createWechatPaymentConfig(): WechatPayment
    {
        return WechatPayment::create([
            'tenant_id' => $this->tenant->getKey(),
            'wechat_id' => 1,
            'name' => '测试商户',
            'mch_id' => 'mch_test',
            'secret' => 'secret_test',
        ]);
    }

    /**
     * 伪造微信支付回调（默认为成功且金额与支付单一致）
     */
    private function fakeWechatNotify(PaymentOrder $payment, array $data, bool $refund = false): void
    {
        $this->mock(WechatPaymentService::class, function (MockInterface $mock) use ($payment, $data, $refund): void {
            $mock->shouldReceive('handleNotify')
                ->once()
                ->andReturn(array_merge([
                    'out_trade_no' => $payment->no,
                    'trade_state' => 'SUCCESS',
                    'amount' => ['total' => (int) bcmul((string) $payment->amount, '100', 0), 'currency' => 'CNY'],
                    'mchid' => 'mch_test',
                ], $data, [
                    'amount' => [
                        'total' => $data['total'] ?? (int) bcmul((string) $payment->amount, '100', 0),
                        'currency' => 'CNY',
                    ],
                ]));

            if ($refund) {
                $mock->shouldReceive('refund')
                    ->once()
                    ->andReturn(new Collection(['status' => 'SUCCESS', 'refund_id' => 'WX_REFUND_AUTO']));
            }
        });
    }

    /**
     * 伪造微信退款成功，供自动退款链路使用
     */
    private function mockWechatRefundSuccess(): void
    {
        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('refund')
                ->andReturn(new Collection(['status' => 'SUCCESS', 'refund_id' => 'WX_REFUND_AUTO']));
        });
    }
}
