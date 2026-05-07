<?php

namespace App\Models\Finance;

use App\Models\Model;
use App\Models\System\Tenant;
use App\Models\Traits\BelongsToUser;
use App\Models\User\User;
use App\Policies\Finance\UserAccountPolicy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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
     */
    public function logs(): HasMany
    {
        return $this->hasMany(UserAccountLog::class, 'user_id');
    }

    /**
     * 关联租户
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
