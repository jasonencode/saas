<?php

namespace Tests\Feature\System;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use PDOException;
use Tests\TestCase;

/**
 * 异常日志分流：业务错误记 warning、安全事件单独标记、系统错误仍记 error
 */
class ExceptionLoggingTest extends TestCase
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

    public function test_business_error_is_logged_as_warning(): void
    {
        Log::spy();

        // 已支付订单不可取消：动作内抛 RuntimeException（file 位于 app/）
        $order = $this->makeOrder($this->user, OrderStatus::Paid);
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders/'.$order->no.'/cancel')
            ->assertStatus(400)
            ->assertJsonPath('message', '订单状态不可从「待发货」变更为「已取消」');

        Log::shouldHaveReceived('warning')
            ->withArgs(static fn (string $message, array $context): bool => ($context['category'] ?? null) === 'business'
                && str_contains($message, '订单状态不可'))
            ->once();

        // 可预期业务错误不再按 5xx 记 ERROR + 堆栈
        Log::shouldNotHaveReceived('error');
    }

    public function test_access_denied_is_logged_as_security_event(): void
    {
        Log::spy();

        $order = $this->makeOrder(User::factory()->create(), OrderStatus::Pending);
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders/'.$order->no.'/cancel')
            ->assertForbidden();

        Log::shouldHaveReceived('warning')
            ->withArgs(static fn (string $message, array $context): bool => ($context['category'] ?? null) === 'security'
                && ($context['event'] ?? null) === 'access_denied'
                && ($context['path'] ?? null) === 'api/mall/orders/'.$order->no.'/cancel')
            ->once();
    }

    public function test_unexpected_exception_is_still_reported_as_error(): void
    {
        Log::spy();

        // 第三方/基础设施异常（PDOException）不属于业务错误，仍应记 ERROR
        Route::middleware('api')->get('/api/_test/db-error', static function (): void {
            throw new PDOException('SQLSTATE[08006] connection refused');
        });

        $this->getJson('/api/_test/db-error')->assertStatus(500);

        Log::shouldHaveReceived('error')->once();
        Log::shouldNotHaveReceived('warning');
    }

    private function makeOrder(User $user, OrderStatus $status): Order
    {
        return Order::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'amount' => '88.00',
            'freight' => '0.00',
            'coupon_discount' => '0.00',
            'fulfillment_type' => FulfillmentType::Virtual,
            'status' => $status,
        ]);
    }
}
