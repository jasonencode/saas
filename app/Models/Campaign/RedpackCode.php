<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Unguarded]
class RedpackCode extends Model
{
    use BelongsToUser,
        SoftDeletes;

    protected $casts = [
        'amount' => 'decimal:2',
        'claimed_at' => 'datetime',
        'status' => RedpackCodeStatus::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (RedpackCode $model) {
            $code = Str::random(6);

            while (static::where('code', $code)->exists()) {
                $code = Str::random(6);
            }

            $model->code = $code;
        });
    }

    /**
     * 所属活动
     */
    public function redpack(): BelongsTo
    {
        return $this->belongsTo(Redpack::class);
    }

    /**
     * 是否可领取
     */
    public function isClaimable(): bool
    {
        return $this->status === RedpackCodeStatus::Active;
    }

    /**
     * 领取红包码
     */
    public function claim(User $user, ?string $ip = null): bool
    {
        if (! $this->isClaimable()) {
            return false;
        }

        return $this->update([
            'user_id' => $user->getKey(),
            'status' => RedpackCodeStatus::Claimed,
            'claimed_at' => now(),
            'claimed_ip' => $ip,
        ]);
    }
}
