<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

class System extends Authenticatable
{
    use Cachable;

    protected function getNameAttribute(): string
    {
        return $this->username;
    }
}