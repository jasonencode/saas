<?php

namespace App\Models\Finance;

use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\User\User;
use App\Policies\Finance\UserAccountPolicy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
#[Table(key: 'user_id')]
#[WithoutIncrementing]
#[UsePolicy(UserAccountPolicy::class)]
class UserAccount extends Model
{
    use BelongsToUser;

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'frozen_balance' => 'decimal:2',
            'points' => 'decimal:2',
            'frozen_points' => 'decimal:2',
        ];
    }

    /**
     * 账户日志
     *
     * @return HasMany<UserAccountLog>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(UserAccountLog::class, 'user_id');
    }

    /**
     * 关联用户
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
