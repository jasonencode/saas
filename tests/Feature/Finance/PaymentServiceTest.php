<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\RechargeOrderStatus;
use App\Enums\Finance\RechargeOrderType;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\RechargeOrder;
use App\Models\Finance\UserAccount;
use App\Models\Foundation\WechatPayment;
use App\Models\Mall\Cart;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Finance\PaymentService;
use App\Services\Finance\RechargeService;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * 支付成功统一出口：标记支付单已支付并推进关联业务
 */
class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PaymentService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant);

        // 订单支付成功会向租户派发通知（走队列），测试环境无需真实投递
        Notification::fake();
    }

    // ─── 充值单 ───────────────────────────────────────────────────

    public function test_mark_paid_with_business_completes_recharge_order(): void
    {
        $recharge = $this->createRechargeOrder(amount: 100, type: RechargeOrderType::Balance);
        $payment = $this->createPayment($recharge);

        $this->service->markPaidWithBusiness($payment, paymentNo: 'WX_123456');

        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);

        $recharge->refresh();
        $this->assertEquals(RechargeOrderStatus::Completed, $recharge->status);
        $this->assertEquals('WX_123456', $recharge->payment_no);
        $this->assertNotNull($recharge->completed_at);

        $account = UserAccount::find($this->user->id);
        $this->assertEquals(100, $account->balance);
        $this->assertDatabaseHas('user_account_logs', [
            'user_id' => $this->user->id,
            'amount' => 100,
            'remark' => "充值单号# {$recharge->no}",
        ]);
    }

    public function test_mark_paid_with_business_is_idempotent_for_recharge(): void
    {
        $recharge = $this->createRechargeOrder(amount: 100, type: RechargeOrderType::Balance);
        $payment = $this->createPayment($recharge);

        $this->service->markPaidWithBusiness($payment, paymentNo: 'WX_001');
        // 微信重复投递回调：不应重复到账、重复改单
        $this->service->markPaidWithBusiness($payment, paymentNo: 'WX_002');

        $account = UserAccount::find($this->user->id);
        $this->assertEquals(100, $account->balance);
        $this->assertSame(1, $account->logs()->count());
        $this->assertEquals(RechargeOrderStatus::Completed, $recharge->fresh()->status);
        $this->assertEquals('WX_001', $recharge->fresh()->payment_no);
    }

    // ─── 商城订单 ─────────────────────────────────────────────────

    public function test_mark_paid_with_business_advances_mall_order(): void
    {
        $order = $this->createPendingOrder();
        $payment = $this->createPayment($order);

        $this->service->markPaidWithBusiness($payment);

        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);
        // 虚拟商品：支付即完成
        $this->assertEquals(OrderStatus::Completed, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_mark_paid_with_business_is_idempotent_for_order(): void
    {
        $order = $this->createPendingOrder();
        $payment = $this->createPayment($order);

        $this->service->markPaidWithBusiness($payment);
        // 第二次应直接返回，不因订单已推进而抛「状态不允许」
        $this->service->markPaidWithBusiness($payment);

        $this->assertEquals(OrderStatus::Completed, $order->fresh()->status);
    }

    // ─── 余额支付 ─────────────────────────────────────────────────

    public function test_pay_by_balance_deducts_balance_and_advances_order(): void
    {
        $account = $this->createAccount(balance: 200, payment_password: '123456');
        $order = $this->createPendingOrder();
        $payment = $this->createPayment($order);

        $this->service->payByBalance($payment, '123456', $this->user);

        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertEquals(OrderStatus::Completed, $order->fresh()->status);

        $account->refresh();
        $this->assertEquals(100, $account->balance);
        $this->assertDatabaseHas('user_account_logs', [
            'user_id' => $this->user->id,
            'amount' => -100,
            'remark' => "支付单号# {$payment->no}",
        ]);
    }

    public function test_pay_by_balance_rejects_recharge_order(): void
    {
        $this->createAccount(balance: 200, payment_password: '123456');
        $recharge = $this->createRechargeOrder(amount: 100, type: RechargeOrderType::Balance);
        $payment = $this->createPayment($recharge);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('充值订单不支持余额支付，请选择其他支付方式');

        $this->service->payByBalance($payment, '123456', $this->user);
    }

    // ─── 微信回调 ─────────────────────────────────────────────────

    public function test_wechat_notify_completes_recharge_order(): void
    {
        $recharge = $this->createRechargeOrder(amount: 100, type: RechargeOrderType::Balance);
        $payment = $this->createPayment($recharge);
        $this->createWechatPaymentConfig();

        $this->fakeWechatNotify($payment, [
            'trade_state' => 'SUCCESS',
            'transaction_id' => 'WX_NOTIFY_001',
        ]);

        $this->postJson("/api/payments/{$payment->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'SUCCESS']);

        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertEquals(RechargeOrderStatus::Completed, $recharge->fresh()->status);
        $this->assertEquals('WX_NOTIFY_001', $recharge->fresh()->payment_no);
        $this->assertEquals(100, UserAccount::find($this->user->id)->balance);
    }

    public function test_wechat_notify_advances_mall_order(): void
    {
        $order = $this->createPendingOrder();
        $payment = $this->createPayment($order);
        $this->createWechatPaymentConfig();

        $this->fakeWechatNotify($payment, ['trade_state' => 'SUCCESS']);

        $this->postJson("/api/payments/{$payment->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'SUCCESS']);

        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertEquals(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_wechat_notify_ignores_unmatched_trade_no(): void
    {
        $recharge = $this->createRechargeOrder(amount: 100, type: RechargeOrderType::Balance);
        $payment = $this->createPayment($recharge);
        $this->createWechatPaymentConfig();

        $this->fakeWechatNotify($payment, [
            'trade_state' => 'SUCCESS',
            'out_trade_no' => 'NOT_MATCHED_NO',
        ]);

        $this->postJson("/api/payments/{$payment->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'FAIL']);

        $this->assertEquals(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertEquals(RechargeOrderStatus::Pending, $recharge->fresh()->status);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function createAccount(float $balance = 0, ?string $payment_password = null): UserAccount
    {
        $account = $this->user->account ?? UserAccount::create(['user_id' => $this->user->id]);
        $account->update([
            'balance' => $balance,
            'payment_password' => $payment_password,
        ]);

        return $account->fresh();
    }

    private function createRechargeOrder(float $amount, RechargeOrderType $type): RechargeOrder
    {
        return service(RechargeService::class)->create(
            userId: $this->user->id,
            tenantId: $this->tenant->id,
            amount: $amount,
            type: $type,
            gateway: PaymentGateway::Wechat,
        );
    }

    /**
     * 创建支付单并关联业务单据
     */
    private function createPayment(Order|RechargeOrder $paymentable): PaymentOrder
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

    /**
     * 通过商城结算接口创建一笔待支付订单（虚拟商品，免运费）
     */
    private function createPendingOrder(): Order
    {
        $product = Product::factory()->for($this->tenant, 'tenant')->create([
            'fulfillment_type' => [FulfillmentType::Virtual->value],
        ]);

        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'price' => '100.00',
            'stock' => 10,
        ]);

        $cart = Cart::query()->firstOrCreate(['user_id' => $this->user->id], ['status' => true]);

        $item = $cart->items()->create([
            'product_id' => $product->id,
            'sku_id' => $sku->id,
            'qty' => 1,
            'price_at_add' => $sku->price,
        ]);

        $this->actingAs($this->user)
            ->withHeader('X-Tenant-Id', (string) $this->tenant->id)
            ->postJson('/api/mall/cart/checkout', [
                'item_ids' => [$item->getKey()],
                'fulfillment_type' => FulfillmentType::Virtual->value,
            ])
            ->assertCreated();

        return Order::query()->latest('id')->firstOrFail();
    }
}
