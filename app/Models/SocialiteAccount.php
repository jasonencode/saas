<?php

namespace App\Models;

use App\Enums\SocialiteProvider;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class SocialiteAccount extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    protected $casts = [
        'provider' => SocialiteProvider::class,
    ];
}
