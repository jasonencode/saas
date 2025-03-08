<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;

class JobBatch extends Model
{
    protected $keyType = 'string';

    protected $casts = [
        'is_finished' => 'bool',
        'cancelled_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Notes   : 获取任务进度
     *
     * @Date   : 2023/8/28 11:08
     * @Author : <Jason.C>
     * @return int
     */
    public function getProcessAttribute(): int
    {
        $batch = Bus::findBatch($this->id);

        return $batch->progress();
    }

    /**
     * Notes   : 批处理是否完成
     *
     * @Date   : 2023/8/28 13:33
     * @Author : <Jason.C>
     * @return bool
     */
    public function getIsFinishedAttribute(): bool
    {
        $batch = Bus::findBatch($this->id);

        return $batch->finished() && !$batch->canceled();
    }

    /**
     * Notes   : 已完成数量
     *
     * @Date   : 2023/8/28 13:33
     * @Author : <Jason.C>
     * @return int
     */
    public function getProcessedJobsAttribute(): int
    {
        $batch = Bus::findBatch($this->id);

        return $batch->processedJobs();
    }

    /**
     * Notes   : 取消状态
     *
     * @Date   : 2023/8/28 13:33
     * @Author : <Jason.C>
     * @return bool
     */
    public function getIsCancelledAttribute(): bool
    {
        $batch = Bus::findBatch($this->id);

        return $batch->canceled();
    }

    /**
     * 取消批处理任务
     *
     * @return void
     */
    public function cancel(): void
    {
        $batch = Bus::findBatch($this->id);

        $batch->cancel();
    }
}
