<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasSortable
{
    #[Scope]
    protected function bySort(Builder $query): void
    {
        $query->orderByDesc('sort')->latest();
    }
}