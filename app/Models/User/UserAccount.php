<?php

namespace App\Models\User;

use App\Models\Model;
use App\Models\Tenant;
use App\Models\Traits\BelongsToUser;
use App\Models\User;
use App\Policies\UserAccountPolicy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * 用户账户模型
 */
#[Unguarded]
#[Table(key: 'user_id')]
#[WithoutIncrementing]
#[UsePolicy(UserAccountPolicy::class)]
class UserAccount extends Model
{
    use BelongsToUser;

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

    /**
     * 关联租户
     *
     * @return HasOneThrough
     */
    public function tenant(): HasOneThrough
    {
        return $this->hasOneThrough(
            Tenant::class,
            User::class,
            'id', // users.id
            'id', // tenants.id
            'user_id', // user_accounts.user_id
            'tenant_id' // users.tenant_id
        );
    }
}
