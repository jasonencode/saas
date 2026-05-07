<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Policies\Mall\AttributeValuePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[UsePolicy(AttributeValuePolicy::class)]
class AttributeValue extends Model
{
    /**
     * 属性关联
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
