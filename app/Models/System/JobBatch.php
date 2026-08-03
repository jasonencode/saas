<?php

namespace App\Models\System;

use App\Policies\System\JobBatchPolicy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;

#[Table(keyType: 'string')]
#[UsePolicy(JobBatchPolicy::class)]
class JobBatch extends Model
{
    protected function casts(): array
    {
        return [
            'is_finished' => 'bool',
            'cancelled_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * 获取任务进度
     *
     * @return int 任务进度（0-100）
     */
    public function getProcessAttribute(): int
    {
        $batch = Bus::findBatch($this->id);

        return $batch ? $batch->progress() : 0;
    }

    /**
     * 批处理是否完成
     *
     * @return bool 是否完成
     */
    public function getIsFinishedAttribute(): bool
    {
        $batch = Bus::findBatch($this->id);

        return $batch && $batch->finished() && !$batch->canceled();
    }

    /**
     * 已完成数量
     *
     * @return int 已完成任务数
     */
    public function getProcessedJobsAttribute(): int
    {
        $batch = Bus::findBatch($this->id);

        return $batch ? $batch->processedJobs() : 0;
    }

    /**
     * 取消状态
     *
     * @return bool 是否已取消
     */
    public function getIsCancelledAttribute(): bool
    {
        $batch = Bus::findBatch($this->id);

        return $batch && $batch->canceled();
    }

    /**
     * 取消批处理任务
     */
    public function cancel(): void
    {
        $batch = Bus::findBatch($this->id);

        $batch?->cancel();
    }
}
