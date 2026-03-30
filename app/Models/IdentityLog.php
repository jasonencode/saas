<?php

namespace App\Models;

use App\Enums\IdentityChannel;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\IdentityLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 身份变更日志模型
 */
#[Unguarded]
#[UsePolicy(IdentityLogPolicy::class)]
class IdentityLog extends Model
{
    use BelongsToTenant,
        BelongsToUser;

    protected $casts = [
        'source' => 'json',
        'channel' => IdentityChannel::class,
    ];

    /**
     * 变更前身份
     */
    public function before(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'before');
    }

    /**
     * 变更后身份
     */
    public function after(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'after');
    }
}
