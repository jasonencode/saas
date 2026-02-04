<?php

namespace App\Models;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    protected $table = 'mall_attribute_values';

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
