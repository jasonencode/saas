<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Models\Traits\BelongsToStore;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use BelongsToStore;

    protected $casts = [
        'type' => DeliveryType::class,
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(DeliveryRule::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
