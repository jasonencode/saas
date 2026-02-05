<?php

namespace App\Models;

use App\Enums\AssetType;
use App\Enums\UserAccountLogType;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserAccountLog extends Model
{
    use BelongsToUser;

    protected $guarded = [];

    protected $casts = [
        'type' => UserAccountLogType::class,
        'flag' => AssetType::class,
        'amount' => 'decimal:2',
        'before' => 'decimal:2',
        'after' => 'decimal:2',
        'extra' => 'array',
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
