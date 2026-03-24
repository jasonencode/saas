<?php

namespace App\Models;

use App\Enums\AliyunDnsType;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(key: 'RecordId')]
class AliyunDns extends Model
{
    protected $casts = [
        'Type' => AliyunDnsType::class,
        'CreateTimestamp' => 'datetime',
        'UpdateTimestamp' => 'datetime',
    ];
}
