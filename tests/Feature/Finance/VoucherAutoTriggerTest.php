<?php

namespace Tests\Feature\Finance;

use App\Contracts\SettlementTask;
use App\Contracts\SettleTaskData;
use App\Enums\Finance\PlanTrigger;
use App\Enums\Finance\VoucherStatus;
use App\Events\Mall\OrderCompleted;
use App\Jobs\Finance\VoucherAutoRunJob;
use App\Listeners\Finance\CreateVoucherOnOrderCompleted;
use App\Models\Finance\Plan;
use App\Models\Finance\Task;
use App\Models\Finance\Voucher;
use App\Models\Mall\Order;
use App\Models\System\Tenant;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 订单完成自动创建结算凭据：触发方式筛选、租户归属、幂等
 */
class VoucherAutoTriggerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // VoucherAutoRunJob 强制 redis 连接，测试环境不实际执行结算
        // （执行链路已由 SettlementServiceTest 覆盖），仅断言调度行为
        Bus::fake([VoucherAutoRunJob::class]);
    }

    /**
     * 订单完成事件已由自动建单监听器订阅
     */
    public function test_order_completed_is_listened_by_voucher_creator(): void
    {
        Event::fake();

        Event::assertListening(OrderCompleted::class, CreateVoucherOnOrderCompleted::class);
    }

    /**
     * 触发方式为「订单完成」的启用计划会创建凭据并调度执行
     */
    public function test_order_completed_creates_voucher_for_order_completed_plans(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->getKey()]);

        $plan = Plan::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'trigger' => PlanTrigger::OrderCompleted,
        ]);

        Task::create([
            'tenant_id' => $tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'name' => '测试步骤',
            'service' => AutoTriggerSucceedTask::class,
            'status' => true,
            'sort' => 0,
        ]);

        (new CreateVoucherOnOrderCompleted)->handle(new OrderCompleted($order));

        $voucher = Voucher::query()->where('plan_id', $plan->getKey())->firstOrFail();

        $this->assertSame($order->getKey(), $voucher->target_id);
        $this->assertSame($order->getMorphClass(), $voucher->target_type);
        $this->assertSame($order->user_id, $voucher->user_id);
        $this->assertSame($tenant->getKey(), $voucher->tenant_id);
        $this->assertSame(VoucherStatus::Pending, $voucher->status);

        Bus::assertDispatchedTimes(VoucherAutoRunJob::class, 1);
    }

    /**
     * 同一订单重复触发事件不会产生重复凭据
     */
    public function test_repeated_order_completed_events_do_not_duplicate_vouchers(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->getKey()]);

        Plan::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'trigger' => PlanTrigger::OrderCompleted,
        ]);

        $listener = new CreateVoucherOnOrderCompleted;
        $event = new OrderCompleted($order);

        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame(1, Voucher::count());
        Bus::assertDispatchedTimes(VoucherAutoRunJob::class, 1);
    }

    /**
     * 手工触发计划不随订单完成自动建单
     */
    public function test_manual_trigger_plans_are_not_triggered_by_order_completed(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->getKey()]);

        Plan::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'trigger' => PlanTrigger::Manual,
        ]);

        (new CreateVoucherOnOrderCompleted)->handle(new OrderCompleted($order));

        $this->assertSame(0, Voucher::count());
        Bus::assertNotDispatched(VoucherAutoRunJob::class);
    }

    /**
     * 禁用的订单完成计划不建单
     */
    public function test_disabled_order_completed_plans_are_ignored(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->getKey()]);

        Plan::factory()->disabled()->create([
            'tenant_id' => $tenant->getKey(),
            'trigger' => PlanTrigger::OrderCompleted,
        ]);

        (new CreateVoucherOnOrderCompleted)->handle(new OrderCompleted($order));

        $this->assertSame(0, Voucher::count());
        Bus::assertNotDispatched(VoucherAutoRunJob::class);
    }
}

class AutoTriggerSucceedTask implements SettlementTask
{
    public function getTitle(): string
    {
        return '测试任务';
    }

    public function getDescription(): string
    {
        return '测试任务';
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function handle(SettleTaskData $data, Closure $next): mixed
    {
        return $next($data);
    }
}
