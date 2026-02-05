<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAccount extends Model
{
    use BelongsToUser;

    protected $primaryKey = 'user_id';

    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
        'frozen_balance' => 'decimal:2',
        'points' => 'decimal:2',
        'frozen_points' => 'decimal:2',
    ];

    /**
     * 账户日志
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(UserAccountLog::class, 'user_id');
    }
}
