<?php

namespace App\Models;

use App\Policies\FailedJobPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;

/**
 * 失败任务模型
 */
#[WithoutTimestamps]
#[UsePolicy(FailedJobPolicy::class)]
class FailedJob extends Model
{
    protected $casts = [
        'payload' => 'json',
        'failed_at' => 'datetime',
    ];
}
