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
    protected $casts = [
        'is_finished' => 'bool',
        'cancelled_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Notes   : 获取任务进度
     *
     * @Date   : 2023/8/28 11:08
     *
     * @Author : <Jason.C>
     */
    public function getProcessAttribute(): int
    {
        $batch = Bus::findBatch($this->id);

        return $batch ? $batch->progress() : 0;
    }

    /**
     * Notes   : 批处理是否完成
     *
     * @Date   : 2023/8/28 13:33
     *
     * @Author : <Jason.C>
     */
    public function getIsFinishedAttribute(): bool
    {
        $batch = Bus::findBatch($this->id);

        return $batch ? ($batch->finished() && !$batch->canceled()) : false;
    }

    /**
     * Notes   : 已完成数量
     *
     * @Date   : 2023/8/28 13:33
     *
     * @Author : <Jason.C>
     */
    public function getProcessedJobsAttribute(): int
    {
        $batch = Bus::findBatch($this->id);

        return $batch ? $batch->processedJobs() : 0;
    }

    /**
     * Notes   : 取消状态
     *
     * @Date   : 2023/8/28 13:33
     *
     * @Author : <Jason.C>
     */
    public function getIsCancelledAttribute(): bool
    {
        $batch = Bus::findBatch($this->id);

        return $batch ? $batch->canceled() : false;
    }

    /**
     * 取消批处理任务
     */
    public function cancel(): void
    {
        $batch = Bus::findBatch($this->id);

        if ($batch) {
            $batch->cancel();
        }
    }
}
