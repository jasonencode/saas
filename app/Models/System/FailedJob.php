<?php

namespace App\Models\System;

use App\Models\Model;
use App\Policies\System\FailedJobPolicy;
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
