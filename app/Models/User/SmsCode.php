<?php

namespace App\Models\User;

use App\Enums\User\SmsChannel;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Unguarded;

#[Unguarded]
class SmsCode extends Model
{
    protected function casts(): array
    {
        return [
            'used' => 'boolean',
            'channel' => SmsChannel::class,
            'expires_at' => 'datetime',
        ];
    }
}
