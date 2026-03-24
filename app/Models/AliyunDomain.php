<?php

namespace App\Models;

use App\Enums\AliyunDomainStatus;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(key: 'InstanceId', keyType: 'string')]
class AliyunDomain extends Model
{
    protected $casts = [
        'AliyunDomainStatus' => AliyunDomainStatus::class,
    ];
}
