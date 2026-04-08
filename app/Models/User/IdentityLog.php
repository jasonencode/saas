<?php

namespace App\Models\User;

use App\Enums\User\IdentityChannel;
use App\Models\Model;
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

    public const null UPDATED_AT = null;

    protected $casts = [
        'source' => 'json',
        'channel' => IdentityChannel::class,
    ];

    /**
     * 变更前身份
     */
    public function beforeIdentity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'before');
    }

    /**
     * 变更后身份
     */
    public function afterIdentity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'after');
    }
}
