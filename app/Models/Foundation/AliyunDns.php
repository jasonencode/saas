<?php

namespace App\Models\Foundation;

use App\Enums\Foundation\AliyunDnsType;
use App\Models\Model;
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
