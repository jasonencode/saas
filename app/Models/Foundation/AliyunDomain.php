<?php

namespace App\Models\Foundation;

use App\Enums\Foundation\AliyunDomainStatus;
use App\Models\Model;
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
