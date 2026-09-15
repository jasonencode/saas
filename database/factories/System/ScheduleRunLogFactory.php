<?php

namespace Database\Factories\System;

use App\Enums\System\ScheduleRunSource;
use App\Enums\System\ScheduleRunStatus;
use App\Models\System\ScheduleRunLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleRunLog>
 */
class ScheduleRunLogFactory extends Factory
{
    protected $model = ScheduleRunLog::class;

    public function definition(): array
    {
        $startedAt = now()->subMinutes(5);

        return [
            'task' => 'app:mall:order-auto-complete',
            'expression' => '0 0 * * *',
            'server' => 'MAIN',
            'status' => ScheduleRunStatus::Success,
            'source' => ScheduleRunSource::Schedule,
            'started_at' => $startedAt,
            'finished_at' => $startedAt->copy()->addSeconds(12),
            'duration_ms' => 12000,
            'exception' => null,
            'context' => null,
            'output' => null,
            'created_at' => $startedAt,
        ];
    }

    /**
     * 设置创建（开始）时间
     */
    public function createdAt(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => $date,
            'created_at' => $date,
        ]);
    }

    /**
     * 设置为执行中
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScheduleRunStatus::Running,
            'finished_at' => null,
            'duration_ms' => null,
        ]);
    }

    /**
     * 设置为手动执行
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => ScheduleRunSource::Manual,
            'expression' => null,
        ]);
    }

    /**
     * 设置为执行失败
     */
    public function failed(string $exception = 'Scheduled command failed with exit code [1].'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScheduleRunStatus::Failed,
            'exception' => $exception,
        ]);
    }
}
