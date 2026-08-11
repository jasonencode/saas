<?php

namespace App\Models\Mall;

use App\Contracts\Authenticatable;
use App\Enums\Mall\OrderLogAction;
use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Policies\Mall\OrderLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Unguarded]
#[UsePolicy(OrderLogPolicy::class)]
class OrderLog extends Model
{
    use BelongsToOrder;

    const null UPDATED_AT = null;

    /**
     * 属性类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => OrderLogAction::class,
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
