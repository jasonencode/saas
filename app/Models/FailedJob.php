<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * 失败任务模型
 */
#[Table(timestamps: false)]
class FailedJob extends Model
{
    protected $casts = [
        'payload' => 'json',
        'failed_at' => 'datetime',
    ];
}
