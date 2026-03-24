<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table(incrementing: true)]
class UserIdentity extends Pivot
{
    use BelongsToUser;

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }

    /**
     * 格式化身份编号
     *
     * @return string
     */
    public function getSerialNoAttribute(): string
    {
        if ($this->identity && $this->identity->serial_open) {
            $prefix = $this->identity->serial_prefix;
            $places = $this->identity->serial_places;

            return $prefix.str_pad($this->serial, $places, "0", STR_PAD_LEFT);
        }

        return '';
    }

    /**
     * 获取最新的身份编号
     *
     * @param  Identity  $identity
     * @return int
     */
    public static function getNewestSerialNo(Identity $identity): int
    {
        $reserve = $identity->serial_reserve;
        $current = self::where('identity_id', $identity->getKey())->max('serial');

        return max($reserve, $current) + 1;
    }
}
