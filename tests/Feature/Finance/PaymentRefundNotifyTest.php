<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Foundation\Wechat;
use App\Models\Foundation\WechatPayment;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Product;
use App\Models\Mall\Refund;
use App\Models\Mall\RefundItem;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Tests\TestCase;
use Yansongda\Supports\Collection;

/**
 * 微信退款回调与对账
 */
class PaymentRefundNotifyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private PaymentOrder $payment;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();

        $this->createWechatPaymentConfig();
        $this->payment = $this->createPayment();
    }

    public function test_success_notify_completes_payment_and_mall_refund(): void
    {
        $refund = $this->createMallRefund();
        $paymentRefund = $this->createPaymentRefund($refund);

        $this->fakeWechatNotify([
            'out_refund_no' => $paymentRefund->no,
            'refund_status' => 'SUCCESS',
            'refund_id' => 'WX_REFUND_NOTIFY',
        ]);

        $this->postJson("/api/payments/refunds/{$paymentRefund->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'SUCCESS']);

        $this->assertSame(PaymentRefundStatus::Completed, $paymentRefund->fresh()->status);
        $this->assertSame(RefundStatus::Completed, $refund->fresh()->status);
        $this->assertDatabaseHas('event_logs', [
            'event' => 'refund.notify_received',
            'subject_id' => $paymentRefund->getKey(),
        ]);
    }

    public function test_notify_with_mismatched_refund_no_is_rejected(): void
    {
        $refund = $this->createMallRefund();
        $paymentRefund = $this->createPaymentRefund($refund);

        $this->fakeWechatNotify([
            'out_refund_no' => 'OTHER_REFUND_NO',
            'refund_status' => 'SUCCESS',
        ]);

        $this->postJson("/api/payments/refunds/{$paymentRefund->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'FAIL']);

        $this->assertSame(PaymentRefundStatus::Processing, $paymentRefund->fresh()->status);
        $this->assertSame(RefundStatus::Processing, $refund->fresh()->status);
    }

    public function test_closed_notify_marks_refund_failed(): void
    {
        $refund = $this->createMallRefund();
        $paymentRefund = $this->createPaymentRefund($refund);

        $this->fakeWechatNotify([
            'out_refund_no' => $paymentRefund->no,
            'refund_status' => 'CLOSED',
        ]);

        $this->postJson("/api/payments/refunds/{$paymentRefund->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'SUCCESS']);

        $this->assertSame(PaymentRefundStatus::Failed, $paymentRefund->fresh()->status);
        $this->assertSame(RefundStatus::Failed, $refund->fresh()->status);
    }

    public function test_duplicate_success_notify_is_idempotent(): void
    {
        $refund = $this->createMallRefund();
        $paymentRefund = $this->createPaymentRefund($refund, PaymentRefundStatus::Completed);

        $this->fakeWechatNotify([
            'out_refund_no' => $paymentRefund->no,
            'refund_status' => 'SUCCESS',
        ]);

        $this->postJson("/api/payments/refunds/{$paymentRefund->id}/notify")
            ->assertOk()
            ->assertJson(['code' => 'SUCCESS']);

        $this->assertSame(PaymentRefundStatus::Completed, $paymentRefund->fresh()->status);
    }

    public function test_reconcile_command_completes_processing_refund(): void
    {
        $refund = $this->createMallRefund();
        $paymentRefund = $this->createPaymentRefund($refund);
        $paymentRefund->update(['created_at' => now()->subMinutes(10)]);

        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('queryRefund')
                ->once()
                ->andReturn(new Collection(['status' => 'SUCCESS']));
        });

        $this->artisan('app:finance:reconcile-payment-refunds')->assertSuccessful();

        $this->assertSame(PaymentRefundStatus::Completed, $paymentRefund->fresh()->status);
        $this->assertSame(RefundStatus::Completed, $refund->fresh()->status);
    }

    public function test_reconcile_command_marks_abnormal_refund_failed(): void
    {
        $refund = $this->createMallRefund();
        $paymentRefund = $this->createPaymentRefund($refund);
        $paymentRefund->update(['created_at' => now()->subMinutes(10)]);

        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('queryRefund')
                ->once()
                ->andReturn(new Collection(['status' => 'ABNORMAL']));
        });

        $this->artisan('app:finance:reconcile-payment-refunds')->assertSuccessful();

        $this->assertSame(PaymentRefundStatus::Failed, $paymentRefund->fresh()->status);
        $this->assertSame(RefundStatus::Failed, $refund->fresh()->status);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function createWechatPaymentConfig(): WechatPayment
    {
        $wechat = Wechat::create([
            'tenant_id' => $this->tenant->id,
            'name' => '测试公众号',
            'app_id' => 'wx_test_app',
            'app_secret' => 'secret',
        ]);

        return WechatPayment::create([
            'tenant_id' => $this->tenant->id,
            'wechat_id' => $wechat->id,
            'name' => '测试商户',
            'mch_id' => 'mch_test',
            'secret' => 'secret_test',
            'status' => true,
        ]);
    }

    private function createPayment(): PaymentOrder
    {
        $order = $this->createOrder();

        $payment = PaymentOrder::create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'gateway' => PaymentGateway::Wechat,
            'amount' => '100.00',
            'expired_at' => now()->addMinutes(30),
            'paymentable' => $order,
        ]);

        $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);

        return $payment;
    }

    private function createOrder(): Order
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => OrderStatus::Paid,
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

    private function createMallRefund(): Refund
    {
        $order = $this->payment->paymentable;

        $refund = Refund::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'order_id' => $order->getKey(),
            'type' => RefundType::OnlyRefund,
            'total' => '100.00',
            'goods_amount' => '100.00',
            'freight_amount' => '0.00',
            'status' => RefundStatus::Processing,
        ]);

        RefundItem::create([
            'refund_id' => $refund->getKey(),
            'order_item_id' => $order->items()->first()->getKey(),
            'qty' => 1,
            'price' => '100.00',
        ]);

        return $refund;
    }

    private function createPaymentRefund(
        Refund $refund,
        PaymentRefundStatus $status = PaymentRefundStatus::Processing,
    ): PaymentRefund {
        return PaymentRefund::create([
            'tenant_id' => $this->tenant->id,
            'payment_order_id' => $this->payment->getKey(),
            'amount' => '100.00',
            'reason' => '商品退款',
            'status' => $status,
            'source' => $refund,
        ]);
    }

    /**
     * 伪造微信退款回调解密结果
     *
     * @param  array<string, mixed>  $resource  回调业务数据
     */
    private function fakeWechatNotify(array $resource): void
    {
        $this->mock(WechatPaymentService::class, function (MockInterface $mock) use ($resource): void {
            $mock->shouldReceive('handleNotify')->once()->andReturn($resource);
        });
    }
}
