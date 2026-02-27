<?php

namespace App\Models;

use App\Enums\AliyunDomainStatus;

class AliyunDomain extends Model
{
    protected $primaryKey = 'InstanceId';

    protected $keyType = 'string';

    protected $casts = [
        'AliyunDomainStatus' => AliyunDomainStatus::class,
    ];
}
