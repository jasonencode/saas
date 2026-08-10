<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Unguarded]
class RedpackCode extends Model
{
    use BelongsToUser,
        SoftDeletes;

    const int CODE_LENGTH_MIN = 6;

    const int CODE_LENGTH_MAX = 16;

    const int CODE_LENGTH_DEFAULT = 6;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'claimed_at' => 'datetime',
            'status' => RedpackCodeStatus::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (RedpackCode $model) {
            if ($model->code !== null) {
                return;
            }

            $code = Str::random(self::CODE_LENGTH_DEFAULT);

            while (static::where('code', $code)->exists()) {
                $code = Str::random(self::CODE_LENGTH_DEFAULT);
            }

            $model->code = $code;
        });
    }

    /**
     * 所属活动
     *
     * @return BelongsTo<Redpack>
     */
    public function redpack(): BelongsTo
    {
        return $this->belongsTo(Redpack::class);
    }

    /**
     * 是否可领取
     *
     * @return bool 是否可领取
     */
    public function isClaimable(): bool
    {
        return $this->status === RedpackCodeStatus::Active;
    }
}
