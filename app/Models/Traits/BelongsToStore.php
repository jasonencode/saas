<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store;

trait BelongsToStore
{
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function setStoreAttribute(Store $store): void
    {
        $this->attributes['store_id'] = $store->getKey();
    }

    public function scopeOfStore(Builder $query, Store|string|null $store): void
    {
        if ($store instanceof Store) {
            $query->where('store_id', $store->getKey());
        } else {
            $query->where('store_id', $store);
        }
    }
}
