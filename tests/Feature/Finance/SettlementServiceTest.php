<?php

namespace Tests\Feature\Finance;

use App\Contracts\SettlementTask;
use App\Contracts\SettleTaskData;
use App\Enums\Finance\VoucherStatus;
use App\Models\Finance\Plan;
use App\Models\Finance\Task;
use App\Models\Finance\Voucher;
use App\Models\Mall\Order;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Finance\SettlementService;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * 结算执行：失败留痕、脏任务、超时回收
 */
class SettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SettlementService::class);
    }

    public function test_successful_settlement_marks_voucher_success(): void
    {
        $voucher = $this->makeVoucher(SucceedSettlementTask::class);

        $this->assertTrue($this->service->execute($voucher));

        $voucher->refresh();
        $this->assertSame(VoucherStatus::Success, $voucher->status);
        $this->assertNotNull($voucher->completed_at);
    }

    public function test_failed_settlement_persists_a_failure_log_after_rollback(): void
    {
        $voucher = $this->makeVoucher(FailingSettlementTask::class);

        try {
            $this->service->execute($voucher);
            $this->fail('结算失败应抛出异常');
        } catch (RuntimeException $e) {
            $this->assertSame('任务执行失败', $e->getMessage());
        }

        $voucher->refresh();
        $this->assertSame(VoucherStatus::Failure, $voucher->status);
        $this->assertStringContainsString('任务执行失败', (string) $voucher->exception);

        // 步骤日志随事务回滚，但结算级失败日志必须独立落库
        $this->assertDatabaseHas('voucher_logs', [
            'voucher_id' => $voucher->getKey(),
            'status' => 'failure',
            'step' => '结算',
        ]);
    }

    public function test_invalid_task_service_throws_instead_of_running_an_empty_pipeline(): void
    {
        $voucher = $this->makeVoucher('App\\Support\\Tasks\\NotExistsTask');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('的服务类不存在');

        $this->service->execute($voucher);
    }

    public function test_voucher_stuck_in_processing_can_be_reclaimed_after_timeout(): void
    {
        $voucher = $this->makeVoucher(SucceedSettlementTask::class, VoucherStatus::Processing);

        // 超过 1 小时未收尾，视为进程异常退出，允许重新抢占
        DB::table('vouchers')
            ->where('id', $voucher->getKey())
            ->update(['updated_at' => now()->subHours(2)]);

        $this->assertTrue($this->service->execute($voucher->refresh()));
        $this->assertSame(VoucherStatus::Success, $voucher->refresh()->status);
    }

    public function test_fresh_processing_voucher_cannot_be_executed_twice(): void
    {
        $voucher = $this->makeVoucher(SucceedSettlementTask::class, VoucherStatus::Processing);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('正在结算中或已完成');

        $this->service->execute($voucher);
    }

    public function test_plan_with_vouchers_cannot_be_force_deleted(): void
    {
        $voucher = $this->makeVoucher(SucceedSettlementTask::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('该计划已产生结算凭据，禁止永久删除');

        $voucher->plan->forceDelete();
    }

    /**
     * 构建结算凭据
     *
     * @param  class-string<SettlementTask>|string  $service  任务服务类
     * @param  VoucherStatus  $status  初始状态
     */
    private function makeVoucher(string $service, VoucherStatus $status = VoucherStatus::Pending): Voucher
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        $plan = Plan::create([
            'tenant_id' => $tenant->getKey(),
            'name' => '测试结算计划',
            'alias' => 'test_'.$tenant->getKey(),
            'status' => true,
            'sort' => 0,
        ]);

        Task::create([
            'tenant_id' => $tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'name' => '测试步骤',
            'service' => $service,
            'status' => true,
            'sort' => 0,
        ]);

        $voucher = Voucher::create([
            'tenant_id' => $tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'user_id' => $user->getKey(),
            'target_type' => Order::class,
            'target_id' => Order::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'user_id' => $user->getKey(),
            ])->getKey(),
        ]);

        if ($status !== VoucherStatus::Pending) {
            $voucher->update(['status' => $status]);
        }

        return $voucher->refresh();
    }
}

/**
 * 结算成功的测试任务
 */
class SucceedSettlementTask implements SettlementTask
{
    public function __construct(protected Task $task) {}

    public function getTitle(): string
    {
        return '成功步骤';
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function getDescription(): string
    {
        return '仅透传，不产生业务副作用';
    }

    public function handle(SettleTaskData $data, Closure $next): mixed
    {
        return $next($data);
    }
}

/**
 * 结算失败的测试任务
 */
class FailingSettlementTask implements SettlementTask
{
    public function __construct(protected Task $task) {}

    public function getTitle(): string
    {
        return '失败步骤';
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function getDescription(): string
    {
        return '固定抛出异常';
    }

    public function handle(SettleTaskData $data, Closure $next): mixed
    {
        throw new RuntimeException('任务执行失败');
    }
}
