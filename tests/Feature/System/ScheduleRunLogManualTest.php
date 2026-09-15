<?php

namespace Tests\Feature\System;

use App\Enums\System\ScheduleRunSource;
use App\Enums\System\ScheduleRunStatus;
use App\Listeners\Schedule\RecordManualCommandRun;
use App\Models\System\ScheduleRunLog;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithConsoleEvents;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

/**
 * 命令行手动执行的留痕
 *
 * CommandStarting / CommandFinished 默认只在非测试环境由 ConsoleKernel 转派，
 * 测试里需要 WithConsoleEvents 打开。
 */
class ScheduleRunLogManualTest extends TestCase
{
    use RefreshDatabase,
        WithConsoleEvents;

    public function test_the_allow_list_comes_from_the_schedule_definition(): void
    {
        $this->assertSame([
            'queue:prune-batches',
            'sanctum:prune-expired',
            'model:prune',
            'app:mall:order-auto-complete',
            'app:user:identity-expire',
            'app:campaign:coupon-expire',
        ], RecordManualCommandRun::getRegisteredTasks());
    }

    public function test_it_records_a_manually_executed_task(): void
    {
        $this->artisan('app:campaign:coupon-expire')->assertExitCode(0);

        $log = ScheduleRunLog::sole();

        $this->assertSame('app:campaign:coupon-expire', $log->task);
        $this->assertSame(ScheduleRunSource::Manual, $log->source);
        $this->assertSame(ScheduleRunStatus::Success, $log->status);
        $this->assertNull($log->expression);
        $this->assertNotNull($log->finished_at);
        $this->assertNotNull($log->duration_ms);
        // 命令内的 logContext() 写进了这条手动记录
        $this->assertSame(['completed' => 0], $log->context);
    }

    public function test_it_records_a_manually_executed_framework_task(): void
    {
        $this->artisan('sanctum:prune-expired --hours=24')->assertExitCode(0);

        $log = ScheduleRunLog::sole();

        $this->assertSame('sanctum:prune-expired', $log->task);
        $this->assertSame(ScheduleRunSource::Manual, $log->source);
    }

    public function test_it_records_all_manual_commands(): void
    {
        $this->artisan('list')->assertExitCode(0);

        // 现在会记录所有手动执行的命令
        $this->assertSame(1, ScheduleRunLog::count());
    }

    public function test_it_ignores_the_child_process_spawned_by_the_scheduler(): void
    {
        // 调度链路：父进程在拉起命令前已登记一条 running 记录
        $scheduled = ScheduleRunLog::factory()->running()->create([
            'task' => 'app:campaign:coupon-expire',
        ]);
        ScheduleRunLog::rememberRun('app:campaign:coupon-expire', '0 0 * * *', (int) $scheduled->getKey());

        $this->artisan('app:campaign:coupon-expire')->assertExitCode(0);

        // 同一次执行只留一条记录，且终态由调度链路（父进程）负责
        $this->assertSame(1, ScheduleRunLog::count());

        $scheduled->refresh();

        $this->assertTrue($scheduled->isRunning());
        // 子进程的 logContext() 写进的是父进程那条记录
        $this->assertSame(['completed' => 0], $scheduled->context);
    }

    public function test_it_records_a_manual_run_after_a_scheduled_run_finished(): void
    {
        $scheduled = ScheduleRunLog::factory()->create(['task' => 'app:campaign:coupon-expire']);
        ScheduleRunLog::rememberRun('app:campaign:coupon-expire', '0 0 * * *', (int) $scheduled->getKey());

        $this->artisan('app:campaign:coupon-expire')->assertExitCode(0);

        $manual = ScheduleRunLog::latest('id')->first();

        $this->assertSame(2, ScheduleRunLog::count());
        $this->assertSame(ScheduleRunSource::Manual, $manual->source);
        $this->assertSame(['completed' => 0], $manual->context);
    }

    public function test_it_ignores_the_child_process_marked_by_the_scheduler(): void
    {
        // 调度器 Event::execute() 会给子进程注入该变量，终端手动执行不会有
        putenv('__LARAVEL_CONTEXT=[]');

        try {
            event(new CommandStarting('app:campaign:coupon-expire', new ArrayInput([]), new NullOutput));
            event(new CommandFinished('app:campaign:coupon-expire', new ArrayInput([]), new NullOutput, 0));
        } finally {
            putenv('__LARAVEL_CONTEXT');
        }

        $this->assertSame(0, ScheduleRunLog::count());
    }

    public function test_it_ignores_the_scheduler_itself(): void
    {
        // cron 拉起的 schedule:run 同样会触发 CommandStarting，但不属于计划任务执行
        $this->artisan('schedule:run')->assertExitCode(0);

        // 进程内跑 schedule:run 会因 everyMinute 任务产生 schedule 来源的记录（父进程监听调度事件写入），
        // 这里只断言没有把调度器自身当成手动执行留痕
        $this->assertSame(0, ScheduleRunLog::where('source', ScheduleRunSource::Manual)->count());
    }

    public function test_it_records_a_failed_manual_run(): void
    {
        $input = new ArrayInput([]);
        $output = new NullOutput;

        event(new CommandStarting('app:campaign:coupon-expire', $input, $output));
        event(new CommandFinished('app:campaign:coupon-expire', $input, $output, 1));

        $log = ScheduleRunLog::sole();

        $this->assertSame(ScheduleRunStatus::Failed, $log->status);
        $this->assertStringContainsString('退出码 1', $log->exception);
        $this->assertNotNull($log->duration_ms);
    }
}
