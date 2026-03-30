<?php

namespace App\Models;

use App\Enums\AliyunDomainStatus;
use App\Policies\AliyunDomainPolicy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#[Table(key: 'InstanceId', keyType: 'string')]
#[UsePolicy(AliyunDomainPolicy::class)]
class AliyunDomain extends Model
{
    protected $casts = [
        'AliyunDomainStatus' => AliyunDomainStatus::class,
    ];
}
