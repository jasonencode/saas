<?php

namespace App\Models\Traits;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait OrderScopes
{
    #[Scope]
    public function ofStatus(Builder $query, OrderStatus $state): void
    {
        $query->where('status', $state);
    }

    #[Scope]
    public function ofPending(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending);
    }

    #[Scope]
    public function ofCanceled(Builder $query): void
    {
        $query->where('status', OrderStatus::Canceled);
    }

    #[Scope]
    public function ofPaid(Builder $query): void
    {
        $query->where('status', OrderStatus::Paid);
    }

    #[Scope]
    public function ofDelivered(Builder $query): void
    {
        $query->where('status', OrderStatus::Delivered);
    }

    #[Scope]
    public function ofSigned(Builder $query): void
    {
        $query->where('status', OrderStatus::Signed);
    }

    #[Scope]
    public function ofCompleted(Builder $query): void
    {
        $query->where('status', OrderStatus::Completed);
    }
}
