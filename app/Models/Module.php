<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';

    protected $casts = [
        'active' => 'boolean',
        'requires' => 'json',
    ];
}
