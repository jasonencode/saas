<?php

namespace App\Models;

use App\Enums\AccountAssetType;
use App\Enums\UserAccountLogType;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 用户账户变动日志模型
 */
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
