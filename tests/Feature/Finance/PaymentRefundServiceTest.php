<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Finance\UserAccount;
use App\Models\Foundation\WechatPayment;
use App\Models\Mall\Cart;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Refund;
use App\Models\Mall\RefundItem;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Finance\PaymentRefundService;
use App\Services\Foundation\WechatPaymentService;
use App\Services\Mall\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;
use Yansongda\Supports\Collection;

/**
 * 支付退款单闭环：申请 → 审核 → 原路退回（微信 / 余额）
 */
class PaymentRefundServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentRefundService $service;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PaymentRefundService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant);

        Notification::fake();

        // 微信退款需要租户已配置商户参数
        $this->createWechatPaymentConfig();
    }

    // ─── 创建 ────────────────────────────────────────────────────

    public function test_create_rejects_unpaid_payment(): void
    {
        $payment = $this->createPayment(gateway: PaymentGateway::Wechat, paid: false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('该订单未支付，无法申请退款');

        $this->service->create($payment, 10);
    }

    public function test_create_rejects_amount_over_refundable(): void
    {
        $payment = $this->createPayment(amount: 100);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('该订单可退款金额不足');

        $this->service->create($payment, 120);
    }

    public function test_create_keeps_pending_refund_occupied(): void
    {
        $payment = $this->createPayment(amount: 100);

        // 第一笔 60 待审核即占用额度，第二笔 60 超出剩余可退
        $this->service->create($payment, 60);

        $this->assertSame('40.00', $this->service->refundableAmount($payment));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('该订单可退款金额不足');

        $this->service->create($payment, 60);
    }

    // ─── 审核 ────────────────────────────────────────────────────

    public function test_approve_marks_refund_approved(): void
    {
        $refund = $this->service->create($this->createPayment(), 100);

        $this->service->approve($refund, 9);

        $refund->refresh();
        $this->assertEquals(PaymentRefundStatus::Approved, $refund->status);
        $this->assertEquals(9, $refund->approved_by);
        $this->assertNotNull($refund->approved_at);
    }

    public function test_reject_marks_refund_rejected(): void
    {
        $refund = $this->service->create($this->createPayment(), 100);

        $this->service->reject($refund, 9, '不符合退款条件');

        $refund->refresh();
        $this->assertEquals(PaymentRefundStatus::Rejected, $refund->status);
        $this->assertEquals('不符合退款条件', $refund->rejected_reason);
    }

    public function test_approve_twice_throws(): void
    {
        $refund = $this->service->create($this->createPayment(), 100);
        $this->service->approve($refund, 9);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('只能审核待处理的退款单');

        $this->service->approve($refund, 9);
    }

    public function test_cancel_marks_refund_cancelled(): void
    {
        $refund = $this->service->create($this->createPayment(), 100);

        $this->service->cancel($refund);

        $this->assertEquals(PaymentRefundStatus::Cancelled, $refund->fresh()->status);
    }

    // ─── 执行：微信原路退回 ───────────────────────────────────────

    public function test_execute_wechat_success_completes_refund(): void
    {
        $payment = $this->createPayment(gateway: PaymentGateway::Wechat, amount: 100);
        $refund = $this->service->create($payment, 100);
        $this->service->approve($refund, 9);

        $this->fakeWechatRefund(['status' => 'SUCCESS', 'refund_id' => 'WX_REFUND_1']);

        $this->service->execute($refund);

        $refund->refresh();
        $this->assertEquals(PaymentRefundStatus::Completed, $refund->status);
        $this->assertEquals('WX_REFUND_1', $refund->channel_refund_no);
        $this->assertNotNull($refund->refunded_at);
    }

    public function test_execute_wechat_failure_records_reason(): void
    {
        $payment = $this->createPayment(gateway: PaymentGateway::Wechat, amount: 100);
        $refund = $this->service->create($payment, 100);
        $this->service->approve($refund, 9);

        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('refund')->once()->andThrow(new RuntimeException('微信退款未受理：余额不足'));
        });

        try {
            $this->service->execute($refund);
            $this->fail('预期退款失败抛异常');
        } catch (RuntimeException) {
            // 预期：通道异常向上抛出
        }

        $refund->refresh();
        $this->assertEquals(PaymentRefundStatus::Failed, $refund->status);
        $this->assertSame('微信退款未受理：余额不足', $refund->failed_reason);
        $this->assertNull($refund->refunded_at);
    }

    public function test_retry_after_failure_completes_refund(): void
    {
        $payment = $this->createPayment(gateway: PaymentGateway::Wechat, amount: 100);
        $refund = $this->service->create($payment, 100);
        $this->service->approve($refund, 9);
        $refund->update(['status' => PaymentRefundStatus::Failed, 'failed_reason' => '通道异常']);

        $this->fakeWechatRefund(['status' => 'SUCCESS', 'refund_id' => 'WX_REFUND_2']);

        $this->service->retry($refund);

        $refund->refresh();
        $this->assertEquals(PaymentRefundStatus::Completed, $refund->status);
        $this->assertNull($refund->failed_reason);
    }

    // ─── 执行：余额退回 ─────────────────────────────────────────

    public function test_execute_balance_refund_credits_user_account(): void
    {
        $this->createAccount(balance: 0);
        $payment = $this->createPayment(gateway: PaymentGateway::Balance, amount: 100);
        $refund = $this->service->create($payment, 100);
        $this->service->approve($refund, 9);

        $this->service->execute($refund);

        $this->assertEquals(PaymentRefundStatus::Completed, $refund->fresh()->status);
        $this->assertEquals(100, UserAccount::find($this->user->id)->balance);
        $this->assertDatabaseHas('user_account_logs', [
            'user_id' => $this->user->id,
            'amount' => 100,
            'remark' => "退款单号# {$refund->no}",
        ]);
    }

    public function test_execute_unsupported_gateway_fails(): void
    {
        $payment = $this->createPayment(gateway: PaymentGateway::Alipay, amount: 100);
        $refund = $this->service->create($payment, 100);
        $this->service->approve($refund, 9);

        try {
            $this->service->execute($refund);
            $this->fail('预期通道不支持抛异常');
        } catch (RuntimeException) {
            // 预期：未接入的通道直接失败
        }

        $refund->refresh();
        $this->assertEquals(PaymentRefundStatus::Failed, $refund->status);
        $this->assertStringContainsString('暂不支持自动原路退回', $refund->failed_reason);
    }

    public function test_execute_twice_throws(): void
    {
        $payment = $this->createPayment(gateway: PaymentGateway::Wechat, amount: 100);
        $refund = $this->service->create($payment, 100);
        $this->service->approve($refund, 9);
        $this->fakeWechatRefund(['status' => 'SUCCESS', 'refund_id' => 'WX_REFUND_1']);
        $this->service->execute($refund);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('只能执行审核通过的退款单');

        $this->service->execute($refund);
    }

    // ─── 与商城售后单打通 ────────────────────────────────────────

    public function test_mall_confirm_refund_creates_pending_payment_refund(): void
    {
        $order = $this->createPendingOrder();
        $payment = $this->createPayment(amount: 100, paymentable: $order);

        $refund = Refund::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'order_id' => $order->getKey(),
            'type' => RefundType::OnlyRefund,
            'total' => 100,
            'goods_amount' => 100,
            'freight_amount' => 0,
            'status' => RefundStatus::Processing,
        ]);

        RefundItem::create([
            'refund_id' => $refund->getKey(),
            'order_item_id' => $order->items()->first()->getKey(),
            'qty' => 1,
            'price' => 100,
        ]);

        service(RefundService::class)->confirmRefund($refund, $this->user, '同意退款');

        $paymentRefund = PaymentRefund::query()->latest('id')->firstOrFail();

        $this->assertEquals($payment->getKey(), $paymentRefund->payment_order_id);
        $this->assertEquals(100, $paymentRefund->amount);
        $this->assertEquals(PaymentRefundStatus::Pending, $paymentRefund->status);
        $this->assertEquals($refund->getMorphClass(), $paymentRefund->source_type);
        $this->assertEquals($refund->getKey(), $paymentRefund->source_id);
        $this->assertEquals("商品退款 #{$refund->no}", $paymentRefund->reason);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function createAccount(float $balance = 0): UserAccount
    {
        $account = $this->user->account ?? UserAccount::create(['user_id' => $this->user->id]);
        $account->update(['balance' => $balance]);

        return $account->fresh();
    }

    /**
     * 创建支付单（默认已支付）
     */
    private function createPayment(
        PaymentGateway $gateway = PaymentGateway::Wechat,
        float $amount = 100,
        bool $paid = true,
        ?Order $paymentable = null,
    ): PaymentOrder {
        $payment = PaymentOrder::create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'gateway' => $gateway,
            'amount' => $amount,
            'expired_at' => now()->addMinutes(30),
            'paymentable' => $paymentable ?? $this->createPendingOrder(),
        ]);

        if ($paid) {
            $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);
        }

        return $payment;
    }

    private function createWechatPaymentConfig(): WechatPayment
    {
        return WechatPayment::create([
            'tenant_id' => $this->tenant->id,
            'wechat_id' => 1,
            'name' => '测试商户',
            'mch_id' => 'mch_test',
            'secret' => 'secret_test',
            'status' => true,
        ]);
    }

    /**
     * 伪造微信退款受理结果
     *
     * @param  array<string, mixed>  $result  微信返回数据
     */
    private function fakeWechatRefund(array $result): void
    {
        $this->mock(WechatPaymentService::class, function (MockInterface $mock) use ($result): void {
            $mock->shouldReceive('refund')->once()->andReturn(new Collection($result));
        });
    }

    /**
     * 通过商城结算接口创建一笔订单（虚拟商品，免运费）
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
