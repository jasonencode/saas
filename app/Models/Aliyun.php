<?php

namespace App\Models;

use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aliyun extends Model
{
    use HasEasyStatus,
        SoftDeletes;

    public function domains(): HasMany
    {
        return $this->hasMany(AliyunDomain::class);
    }

    public function dns(): HasMany
    {
        return $this->hasMany(AliyunDns::class);
    }
}
