<?php

namespace Tests\Feature\Mall;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Events\Mall\RefundInitialized;
use App\Jobs\Mall\ProcessExpressRefundJob;
use App\Listeners\Mall\ExpressRefundOnInitialized;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\UserAccount;
use App\Models\Foundation\WechatPayment;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\Mall\StoreConfigure;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Foundation\WechatPaymentService;
use App\Services\Mall\DTOs\RefundData;
use App\Services\Mall\DTOs\RefundItemData;
use App\Services\Mall\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;
use Yansongda\Supports\Collection;

/**
 * 极速退款：未发货订单的仅退款申请后自动原路退回
 */
class ExpressRefundTest extends TestCase
{
    use RefreshDatabase;

    private RefundService $service;

    private Tenant $tenant;

    private User $user;

    private StoreConfigure $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RefundService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant);

        config()->set('custom.mall.express_refund', [
            'enabled' => true,
            'max_amount' => 500,
            'daily_limit' => 5,
        ]);

        $this->store = $this->configureStore();
    }

    public function test_balance_refund_completes_automatically(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order);
        $account = $this->createAccount();

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        $this->assertTrue($refund->is_express);

        (new ProcessExpressRefundJob($refund))->handle();

        $refund->refresh();
        $paymentRefund = $refund->paymentRefunds()->firstOrFail();

        $this->assertSame(RefundStatus::Completed, $refund->status);
        $this->assertNotNull($refund->refund_at);
        $this->assertSame(PaymentRefundStatus::Completed, $paymentRefund->status);
        $this->assertNotNull($paymentRefund->approved_at);
        $this->assertNull($paymentRefund->approved_by);
        $this->assertEquals('100.00', $account->fresh()->balance);
        $this->assertDatabaseHas('event_logs', [
            'subject_type' => $refund->getMorphClass(),
            'subject_id' => $refund->getKey(),
            'event' => 'refund.express',
        ]);
    }

    public function test_express_refund_is_idempotent(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order);
        $account = $this->createAccount();

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        (new ProcessExpressRefundJob($refund))->handle();
        (new ProcessExpressRefundJob($refund))->handle();

        $this->assertSame(1, $refund->paymentRefunds()->count());
        $this->assertEquals('100.00', $account->fresh()->balance);
    }

    public function test_store_switch_off_keeps_manual_flow(): void
    {
        $this->store->update(['express_refund_enabled' => false]);

        $order = $this->createOrder();
        $this->createPayment($order);

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        $this->assertFalse($refund->is_express);

        (new ProcessExpressRefundJob($refund))->handle();

        $this->assertSame(RefundStatus::Processing, $refund->fresh()->status);
        $this->assertSame(0, $refund->paymentRefunds()->count());
    }

    public function test_platform_switch_off_keeps_manual_flow(): void
    {
        config()->set('custom.mall.express_refund.enabled', false);

        $order = $this->createOrder();
        $this->createPayment($order);

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        $this->assertFalse($refund->is_express);
    }

    public function test_amount_over_store_limit_is_not_express(): void
    {
        $this->store->update(['express_refund_max_amount' => 50]);

        $order = $this->createOrder();
        $this->createPayment($order);

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        $this->assertFalse($refund->is_express);
    }

    public function test_daily_limit_blocks_second_express_refund(): void
    {
        $this->store->update(['express_refund_daily_limit' => 1]);

        $firstOrder = $this->createOrder();
        $this->createPayment($firstOrder);
        $firstRefund = $this->service->createRefund($firstOrder, $this->user, $this->refundData($firstOrder));

        $secondOrder = $this->createOrder();
        $this->createPayment($secondOrder);
        $secondRefund = $this->service->createRefund($secondOrder, $this->user, $this->refundData($secondOrder));

        $this->assertTrue($firstRefund->is_express);
        $this->assertFalse($secondRefund->is_express);
    }

    public function test_unsupported_gateway_is_not_express(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentGateway::Manual);

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        $this->assertFalse($refund->is_express);
    }

    public function test_shipped_order_is_not_express(): void
    {
        $order = $this->createOrder(OrderStatus::Delivered);
        $this->createPayment($order);

        $refund = $this->service->createRefund(
            $order,
            $this->user,
            $this->refundData($order, RefundType::ReturnRefund),
        );

        $this->assertFalse($refund->is_express);
    }

    public function test_wechat_processing_keeps_refund_in_processing(): void
    {
        $this->createWechatPaymentConfig();

        $order = $this->createOrder();
        $this->createPayment($order, PaymentGateway::Wechat);

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        $this->fakeWechatRefund(['status' => 'PROCESSING', 'refund_id' => 'WX_PROCESSING']);

        (new ProcessExpressRefundJob($refund))->handle();

        $refund->refresh();
        $paymentRefund = $refund->paymentRefunds()->firstOrFail();

        $this->assertSame(RefundStatus::Processing, $refund->status);
        $this->assertSame(PaymentRefundStatus::Processing, $paymentRefund->status);
        $this->assertSame('WX_PROCESSING', $paymentRefund->channel_refund_no);
    }

    public function test_wechat_failure_marks_refund_failed(): void
    {
        $this->createWechatPaymentConfig();

        $order = $this->createOrder();
        $this->createPayment($order, PaymentGateway::Wechat);

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('refund')->once()->andThrow(new RuntimeException('通道异常'));
        });

        try {
            (new ProcessExpressRefundJob($refund))->handle();
            $this->fail('通道异常时应抛出异常');
        } catch (RuntimeException) {
            // 预期异常：售后单转失败等待后台重新发起
        }

        $refund->refresh();
        $paymentRefund = $refund->paymentRefunds()->firstOrFail();

        $this->assertSame(RefundStatus::Failed, $refund->status);
        $this->assertSame(PaymentRefundStatus::Failed, $paymentRefund->status);
        $this->assertSame('通道异常', $paymentRefund->failed_reason);

        // 已失败的单再次执行不会重复创建支付退款
        (new ProcessExpressRefundJob($refund))->handle();
        $this->assertSame(1, $refund->paymentRefunds()->count());
    }

    public function test_listener_dispatches_job_for_express_refund(): void
    {
        Queue::fake();

        $order = $this->createOrder();
        $this->createPayment($order);

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        (new ExpressRefundOnInitialized)->handle(new RefundInitialized($refund));

        Queue::assertPushed(ProcessExpressRefundJob::class);
    }

    public function test_listener_ignores_normal_refund(): void
    {
        Queue::fake();

        $this->store->update(['express_refund_enabled' => false]);

        $order = $this->createOrder();
        $this->createPayment($order);

        $refund = $this->service->createRefund($order, $this->user, $this->refundData($order));

        (new ExpressRefundOnInitialized)->handle(new RefundInitialized($refund));

        Queue::assertNothingPushed();
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function configureStore(): StoreConfigure
    {
        return StoreConfigure::create([
            'tenant_id' => $this->tenant->id,
            'store_name' => '测试店铺',
            'cover' => null,
            'enabled' => true,
            'express_refund_enabled' => true,
        ]);
    }

    private function createOrder(OrderStatus $status = OrderStatus::Paid): Order
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => $status,
            'fulfillment_type' => FulfillmentType::Mail,
            'amount' => '100.00',
            'freight' => '0.00',
        ]);

        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'price' => '100.00',
            'stock' => 10,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'orderable_type' => $sku->getMorphClass(),
            'orderable_id' => $sku->id,
            'qty' => 1,
            'price' => '100.00',
        ]);

        return $order->load('items');
    }

    private function createPayment(Order $order, PaymentGateway $gateway = PaymentGateway::Balance): PaymentOrder
    {
        $payment = PaymentOrder::create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'gateway' => $gateway,
            'amount' => '100.00',
            'expired_at' => now()->addMinutes(30),
            'paymentable' => $order,
        ]);

        $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);

        return $payment;
    }

    private function createAccount(string $balance = '0.00'): UserAccount
    {
        $account = UserAccount::find($this->user->id) ?? UserAccount::create(['user_id' => $this->user->id]);
        $account->update(['balance' => $balance]);

        return $account->fresh();
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

    private function refundData(Order $order, RefundType $type = RefundType::OnlyRefund): RefundData
    {
        return RefundData::make(
            type: $type,
            reason: RefundReason::NotWant,
            items: [RefundItemData::make(orderItemId: (int) $order->items->first()->getKey(), qty: 1)],
        );
    }
}
