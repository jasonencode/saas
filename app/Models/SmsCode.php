<?php

namespace App\Models;

use App\Enums\SmsChannel;
use Illuminate\Database\Eloquent\Attributes\Unguarded;

/**
 * 短信验证码模型
 */
#[Unguarded]
class SmsCode extends Model
{
    protected $casts = [
        'used' => 'boolean',
        'channel' => SmsChannel::class,
        'expires_at' => 'timestamp',
    ];
}
