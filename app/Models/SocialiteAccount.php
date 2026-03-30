<?php

namespace App\Models;

use App\Enums\SocialiteProvider;
use App\Models\Traits\BelongsToTenant;
use App\Policies\SocialiteAccountPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(SocialiteAccountPolicy::class)]
class SocialiteAccount extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    protected $casts = [
        'provider' => SocialiteProvider::class,
    ];
}
