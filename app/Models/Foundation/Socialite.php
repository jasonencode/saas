<?php

namespace App\Models\Foundation;

use App\Enums\Foundation\SocialiteProvider;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\Foundation\SocialitePolicy;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['access_token', 'refresh_token'])]
#[Unguarded]
#[UsePolicy(SocialitePolicy::class)]
class Socialite extends Model
{
    use BelongsToTenant,
        BelongsToUser;

    protected function casts(): array
    {
        return [
            'provider' => SocialiteProvider::class,
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expired_at' => 'datetime',
        ];
    }

    /**
     * 关联社交账号
     *
     * @return BelongsTo<SocialiteAccount>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialiteAccount::class);
    }
}
