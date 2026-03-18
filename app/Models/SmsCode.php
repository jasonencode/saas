<?php

namespace App\Models;

use App\Enums\SmsChannel;

/**
 * 短信验证码模型
 */
class SmsCode extends Model
{
    protected $casts = [
        'used' => 'boolean',
        'channel' => SmsChannel::class,
        'expires_at' => 'timestamp',
    ];
}
