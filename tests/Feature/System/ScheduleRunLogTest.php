<?php

namespace Tests\Feature\System;

use App\Enums\System\ScheduleRunStatus;
use App\Models\System\Administrator;
use App\Models\System\ScheduleRunLog;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Tests\TestCase;

class ScheduleRunLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 构建一个与 routes/console.php 等价的调度事件
     *
     * @param  string  $command  命令签名（可带参数）
     */
    protected function scheduledTask(string $command = 'app:mall:order-auto-complete'): Event
    {
        return app(Schedule::class)->command($command)->daily();
    }

    public function test_it_records_a_successful_run(): void
    {
        $task = $this->scheduledTask();

        event(new ScheduledTaskStarting($task));

        $log = ScheduleRunLog::sole();

        $this->assertSame('app:mall:order-auto-complete', $log->task);
        $this->assertSame('0 0 * * *', $log->expression);
        $this->assertTrue($log->isRunning());
        $this->assertNull($log->finished_at);
        $this->assertNotNull($log->started_at);

        event(new ScheduledTaskFinished($task, 1.234));

        $log->refresh();

        $this->assertSame(ScheduleRunStatus::Success, $log->status);
        $this->assertSame(1234, $log->duration_ms);
        $this->assertNotNull($log->finished_at);
    }

    public function test_it_parses_the_signature_from_a_command_with_parameters(): void
    {
        event(new ScheduledTaskStarting($this->scheduledTask('sanctum:prune-expired --hours=24')));

        $this->assertSame('sanctum:prune-expired', ScheduleRunLog::sole()->task);
    }

    public function test_it_names_closure_tasks(): void
    {
        event(new ScheduledTaskStarting(app(Schedule::class)->call(static fn () => null)));

        $this->assertStringStartsWith('callback:', ScheduleRunLog::sole()->task);
    }

    public function test_a_failed_run_overrides_the_success_recorded_by_finished(): void
    {
        $task = $this->scheduledTask();

        // 非零退出码时框架先派发 Finished（携带耗时），再补发 Failed
        event(new ScheduledTaskStarting($task));
        event(new ScheduledTaskFinished($task, 1.5));
        event(new ScheduledTaskFailed($task, new RuntimeException('Scheduled command failed with exit code [1].')));

        $log = ScheduleRunLog::sole();

        $this->assertSame(ScheduleRunStatus::Failed, $log->status);
        $this->assertSame(1500, $log->duration_ms);
        $this->assertStringContainsString('exit code [1]', $log->exception);
    }

    public function test_it_falls_back_to_started_at_when_finished_is_missing(): void
    {
        $task = $this->scheduledTask();

        $this->travelTo(now()->startOfSecond());

        event(new ScheduledTaskStarting($task));

        $this->travel(5)->seconds();

        event(new ScheduledTaskFailed($task, new RuntimeException('before callback failed')));

        $this->assertSame(5000, ScheduleRunLog::sole()->duration_ms);
    }

    public function test_log_context_merges_into_the_running_log(): void
    {
        event(new ScheduledTaskStarting($this->scheduledTask()));

        ScheduleRunLog::updateCurrentContext('app:mall:order-auto-complete', ['completed' => 37]);
        ScheduleRunLog::updateCurrentContext('app:mall:order-auto-complete', ['failed' => 2]);

        $log = ScheduleRunLog::sole();

        $this->assertSame(['completed' => 37, 'failed' => 2], $log->context);
        $this->assertTrue($log->hasErrors());
    }

    public function test_log_context_is_noop_when_the_command_runs_manually(): void
    {
        $log = ScheduleRunLog::factory()->create(['task' => 'app:mall:order-auto-complete']);

        ScheduleRunLog::updateCurrentContext('app:mall:order-auto-complete', ['completed' => 1]);

        $this->assertNull($log->refresh()->context);
    }

    public function test_log_context_is_noop_after_the_run_has_finished(): void
    {
        $task = $this->scheduledTask();

        event(new ScheduledTaskStarting($task));
        event(new ScheduledTaskFinished($task, 1.0));

        ScheduleRunLog::updateCurrentContext('app:mall:order-auto-complete', ['completed' => 1]);

        $this->assertNull(ScheduleRunLog::sole()->context);
    }

    public function test_log_context_survives_a_stale_registry_entry(): void
    {
        Cache::put(ScheduleRunLog::taskKey('app:mall:order-auto-complete'), 999999, 60);

        ScheduleRunLog::updateCurrentContext('app:mall:order-auto-complete', ['completed' => 1]);

        $this->assertSame(0, ScheduleRunLog::count());
    }

    public function test_stale_scope_returns_runs_stuck_over_an_hour(): void
    {
        $stuck = ScheduleRunLog::factory()->running()->create([
            'started_at' => now()->subHours(2),
            'created_at' => now()->subHours(2),
        ]);
        ScheduleRunLog::factory()->running()->create();

        $this->assertSame(1, ScheduleRunLog::stale()->count());
        $this->assertTrue(ScheduleRunLog::stale()->sole()->is($stuck));
    }

    public function test_prunable_scope_returns_logs_older_than_90_days(): void
    {
        ScheduleRunLog::factory()->createdAt(now()->subDays(100)->toDateTimeString())->create();
        ScheduleRunLog::factory()->createdAt(now()->subDays(30)->toDateTimeString())->create();

        $this->assertSame(1, (new ScheduleRunLog)->prunable()->count());
    }

    public function test_pruning_deletes_old_logs(): void
    {
        $old = ScheduleRunLog::factory()->createdAt(now()->subDays(100)->toDateTimeString())->create();
        $recent = ScheduleRunLog::factory()->create();

        (new ScheduleRunLog)->prunable()->delete();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_it_formats_duration_for_humans(): void
    {
        $this->assertSame('-', ScheduleRunLog::formatDuration(null));
        $this->assertSame('350ms', ScheduleRunLog::formatDuration(350));
        $this->assertSame('1.5s', ScheduleRunLog::formatDuration(1500));
        $this->assertSame('2m 5s', ScheduleRunLog::formatDuration(125000));
    }

    public function test_policy_denies_an_administrator_without_permission(): void
    {
        Administrator::factory()->create(); // 占用 ID 1（超级管理员）
        $admin = Administrator::factory()->create();

        $this->assertFalse(Gate::forUser($admin)->allows('viewAny', ScheduleRunLog::class));
        $this->assertFalse(Gate::forUser($admin)->allows('view', ScheduleRunLog::factory()->create()));
    }

    public function test_policy_allows_the_super_administrator(): void
    {
        $admin = Administrator::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', ScheduleRunLog::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', ScheduleRunLog::factory()->create()));
    }
}
