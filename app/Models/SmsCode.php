<?php

namespace App\Models;

use App\Enums\SmsChannel;

class SmsCode extends Model
{
    protected $casts = [
        'used' => 'boolean',
        'channel' => SmsChannel::class,
        'expires_at' => 'timestamp',
    ];
}
