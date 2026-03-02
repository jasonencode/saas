<?php

namespace App\Models;

use App\Enums\AliyunInstanceChargeType;

class AliyunEcs extends Model
{
    protected $primaryKey = 'InstanceId';

    protected $keyType = 'string';

    protected $casts = [
        'ExpiredTime' => 'datetime',
        'CreationTime' => 'datetime',
        'InstanceChargeType' => AliyunInstanceChargeType::class,
    ];
}