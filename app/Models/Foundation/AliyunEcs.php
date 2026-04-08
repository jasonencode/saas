<?php

namespace App\Models\Foundation;

use App\Enums\Foundation\AliyunInstanceChargeType;
use App\Models\Model;
use App\Policies\AliyunEcsPolicy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#[Table(key: 'InstanceId', keyType: 'string')]
#[UsePolicy(AliyunEcsPolicy::class)]
class AliyunEcs extends Model
{
    protected $casts = [
        'ExpiredTime' => 'datetime',
        'CreationTime' => 'datetime',
        'InstanceChargeType' => AliyunInstanceChargeType::class,
    ];
}