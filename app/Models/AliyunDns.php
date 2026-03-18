<?php

namespace App\Models;

use App\Enums\AliyunDnsType;

class AliyunDns extends Model
{
    protected $primaryKey = 'RecordId';

    protected $casts = [
        'Type' => AliyunDnsType::class,
        'CreateTimestamp' => 'datetime',
        'UpdateTimestamp' => 'datetime',
    ];
}
