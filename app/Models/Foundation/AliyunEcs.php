<?php

namespace App\Models\Foundation;

use App\Enums\Foundation\AliyunInstanceChargeType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(key: 'InstanceId', keyType: 'string')]
class AliyunEcs extends Model
{
    protected function casts(): array
    {
        return [
            'ExpiredTime' => 'datetime',
            'CreationTime' => 'datetime',
            'InstanceChargeType' => AliyunInstanceChargeType::class,
        ];
    }
}
