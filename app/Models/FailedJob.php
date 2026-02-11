<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 失败任务模型
 */
class FailedJob extends Model
{
    public $timestamps = false;

    protected $casts = [
        'payload' => 'json',
        'failed_at' => 'datetime',
    ];
}
