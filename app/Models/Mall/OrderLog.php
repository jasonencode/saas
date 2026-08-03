<?php

namespace App\Models\Mall;

use App\Enums\Mall\OrderLogAction;
use App\Models\Model;
use App\Models\System\Administrator;
use App\Models\Traits\BelongsToOrder;
use App\Policies\Mall\OrderLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }
}
