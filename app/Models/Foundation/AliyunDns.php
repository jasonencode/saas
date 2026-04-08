<?php

namespace App\Models\Foundation;

use App\Enums\Foundation\AliyunDnsType;
use App\Models\Model;
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
