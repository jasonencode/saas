<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\DeliveryType;
use App\Models\Traits\BelongsToStore;

class Delivery extends Model
{
    use BelongsToStore;

    protected $table = 'mall_deliveries';

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
