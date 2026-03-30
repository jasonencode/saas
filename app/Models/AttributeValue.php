<?php

namespace App\Models;

use App\Policies\AttributeValuePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 属性值模型
 */
#[Unguarded]
#[UsePolicy(AttributeValuePolicy::class)]
class AttributeValue extends Model
{
    /**
     * 属性关联
     *
     * @return BelongsTo
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
