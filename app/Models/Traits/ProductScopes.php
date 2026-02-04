<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Enums\ProductStatus;

trait ProductScopes
{
    public function scopeOfStatus(Builder $query, string $status): void
    {
        $status = ProductStatus::tryFrom($status);
        if ($status) {
            $query->where('status', $status);
        }
    }

    public function scopeOfPending(Builder $query): void
    {
        $query->where('status', ProductStatus::Pending);
    }

    public function scopeOfPass(Builder $query): void
    {
        $query->where('status', ProductStatus::Approved);
    }

    public function scopeOfUp(Builder $query): void
    {
        $query->where('status', ProductStatus::Up);
    }

    public function scopeOfReject(Builder $query): void
    {
        $query->where('status', ProductStatus::Rejected);
    }

    public function scopeOfDown(Builder $query): void
    {
        $query->where('status', ProductStatus::Down);
    }
}
