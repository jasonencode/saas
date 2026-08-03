<?php

namespace App\Models\Finance;

use App\Enums\Finance\AccountAssetType;
use App\Enums\User\UserAccountLogType;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Unguarded]
class UserAccountLog extends Model
{
    use BelongsToUser;

    protected function casts(): array
    {
        return [
            'type' => UserAccountLogType::class,
            'asset' => AccountAssetType::class,
            'amount' => 'decimal:2',
            'before' => 'decimal:2',
            'after' => 'decimal:2',
            'extra' => 'json',
        ];
    }

    /**
     * 变动关联的来源
     *
     * @return MorphTo<Model>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
