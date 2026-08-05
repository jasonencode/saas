<?php

namespace App\Models\System;

use App\Models\Model;

class DBLog extends Model
{
    protected $table = 'logs';

    protected $fillable = [
        'message',
        'channel',
        'level',
        'level_name',
        'unix_time',
        'datetime',
        'context',
        'extra',
    ];

    protected $casts = [
        'context' => 'array',
        'extra' => 'array',
        'unix_time' => 'integer',
        'level' => 'integer',
    ];
}
