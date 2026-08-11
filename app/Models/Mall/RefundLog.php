<?php

namespace App\Models\Mall;

use App\Contracts\Authenticatable;
use App\Enums\Mall\RefundLogAction;
use App\Models\Model;
use App\Models\Traits\BelongsToRefund;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Unguarded]
class RefundLog extends Model
{
    use BelongsToRefund;

    const null UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'action' => RefundLogAction::class,
            'context' => 'json',
        ];
    }

    /**
     * 操作人
     *
     * @return MorphTo<Authenticatable>
     */
    public function operator(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 设置操作人
     *
     * @param  Authenticatable  $user
     */
    public function setOperatorAttribute(Authenticatable $user): void
    {
        $this->attributes['operator_type'] = $user->getMorphClass();
        $this->attributes['operator_id'] = $user->getKey();
    }
}
