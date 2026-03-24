<?php

namespace App\Models;

use App\Enums\AliyunInstanceChargeType;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(key: 'InstanceId', keyType: 'string')]
class AliyunEcs extends Model
{
    protected $casts = [
        'ExpiredTime' => 'datetime',
        'CreationTime' => 'datetime',
        'InstanceChargeType' => AliyunInstanceChargeType::class,
    ];
}