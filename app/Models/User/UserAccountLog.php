<?php

namespace App\Models\User;

use App\Enums\User\AccountAssetType;
use App\Enums\User\UserAccountLogType;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Policies\UserAccountLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 用户账户变动日志模型
 */
#[Unguarded]
#[UsePolicy(UserAccountLogPolicy::class)]
class UserAccountLog extends Model
{
    use BelongsToUser;

    protected $casts = [
        'type' => UserAccountLogType::class,
        'asset' => AccountAssetType::class,
        'amount' => 'decimal:2',
        'before' => 'decimal:2',
        'after' => 'decimal:2',
        'extra' => 'json',
    ];

    /**
     * 变动关联的来源
     *
     * @return MorphTo
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
