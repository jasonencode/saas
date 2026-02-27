<?php

namespace App\Models;

use App\Enums\DomainStatus;

class AliyunDomain extends Model
{
    protected $primaryKey = 'InstanceId';

    protected $keyType = 'string';

    protected $casts = [
        'DomainStatus' => DomainStatus::class,
    ];
}
