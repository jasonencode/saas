<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;

class Module extends Model
{
    use Cachable;

    protected $casts = [
        'status' => 'bool',
    ];
}