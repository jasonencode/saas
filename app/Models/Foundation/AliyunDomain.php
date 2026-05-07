<?php

namespace App\Models\Foundation;

use App\Enums\Foundation\AliyunDomainStatus;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(key: 'InstanceId', keyType: 'string')]
class AliyunDomain extends Model
{
    protected $casts = [
        'AliyunDomainStatus' => AliyunDomainStatus::class,
    ];
}
