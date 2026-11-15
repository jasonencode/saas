<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Events\Mall\RefundApproved;
use App\Events\Mall\RefundCompleted;
use App\Events\Mall\RefundFailed;
use App\Events\Mall\RefundRejected;
use App\Listeners\Mall\RefundApprovedListener;
use App\Listeners\Mall\RefundCompletedListener;
use App\Listeners\Mall\RefundFailedListener;
use App\Listeners\Mall\RefundRejectedListener;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Notifications\Mall\RefundApprovedNotification;
use App\Notifications\Mall\RefundCompletedUserNotification;
use App\Notifications\Mall\RefundFailedUserNotification;
use App\Notifications\Mall\RefundRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * 退款结果通知用户
 */
class RefundNotificationTest extends TestCase
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
    }

    public function test_completed_listener_notifies_user(): void
    {
        $refund = $this->createRefund();

        (new RefundCompletedListener)->handle(new RefundCompleted($refund));

        Notification::assertSentTo($this->user, RefundCompletedUserNotification::class);
    }

    public function test_failed_listener_notifies_user(): void
    {
        $refund = $this->createRefund();

        (new RefundFailedListener)->handle(new RefundFailed($refund));

        Notification::assertSentTo($this->user, RefundFailedUserNotification::class);
    }

    public function test_approved_listener_notifies_user(): void
    {
        $refund = $this->createRefund();

        (new RefundApprovedListener)->handle(new RefundApproved($refund));

        Notification::assertSentTo($this->user, RefundApprovedNotification::class);
    }

    public function test_rejected_listener_notifies_user_with_reason(): void
    {
        $refund = $this->createRefund();
        $refund->update(['status' => 'rejected', 'approval_remark' => '商品已签收超过 7 天']);

        (new RefundRejectedListener)->handle(new RefundRejected($refund->fresh()));

        Notification::assertSentTo($this->user, RefundRejectedNotification::class);
    }

    private function createRefund(): Refund
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => OrderStatus::Paid,
            'fulfillment_type' => FulfillmentType::Mail,
            'amount' => '100.00',
            'freight' => '0.00',
        ]);

        return Refund::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'order_id' => $order->getKey(),
            'type' => 'only_refund',
            'total' => '100.00',
            'goods_amount' => '100.00',
            'freight_amount' => '0.00',
            'status' => 'processing',
        ]);
    }
}
