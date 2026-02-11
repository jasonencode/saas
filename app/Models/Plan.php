<?php

namespace App\Models;

use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasEasyStatus,
        HasSortable,
        SoftDeletes;

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
