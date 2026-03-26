<?php

namespace App\Models;

use App\Enums\RedpackCodeStatus;
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
     *
     * @return BelongsTo
     */
    public function redpack(): BelongsTo
    {
        return $this->belongsTo(Redpack::class);
    }
}
