<?php

namespace App\Models\User;

use App\Enums\User\IdentityChannel;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class IdentityLog extends Model
{
    use BelongsToTenant,
        BelongsToUser;

    const null UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'source' => 'json',
            'channel' => IdentityChannel::class,
        ];
    }

    /**
     * 变更前身份
     *
     * @return BelongsTo<Identity>
     */
    public function beforeIdentity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'before');
    }

    /**
     * 变更后身份
     *
     * @return BelongsTo<Identity>
     */
    public function afterIdentity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'after');
    }
}
