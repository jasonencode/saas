<?php

namespace App\Models;

use App\Enums\AliyunDnsType;
use App\Policies\AliyunDnsPolicy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#[Table(key: 'RecordId')]
#[UsePolicy(AliyunDnsPolicy::class)]
class AliyunDns extends Model
{
    protected $casts = [
        'Type' => AliyunDnsType::class,
        'CreateTimestamp' => 'datetime',
        'UpdateTimestamp' => 'datetime',
    ];
}
