<?php

namespace App\Models;

use App\Enums\SmsChannel;
use App\Policies\SmsCodePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

/**
 * 短信验证码模型
 */
#[Unguarded]
#[UsePolicy(SmsCodePolicy::class)]
class SmsCode extends Model
{
    protected $casts = [
        'used' => 'boolean',
        'channel' => SmsChannel::class,
        'expires_at' => 'datetime',
    ];
}
