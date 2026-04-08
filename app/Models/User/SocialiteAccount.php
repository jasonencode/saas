<?php

namespace App\Models\User;

use App\Enums\Foundation\SocialiteProvider;
use App\Models\Model;
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

    public function getConfig(): array
    {
        return [

        ];
    }
}
