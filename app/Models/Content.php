<?php

namespace App\Models;

use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use Cachable,
        HasCovers,
        HasEasyStatus,
        SoftDeletes;

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'content_category')
            ->withTimestamps();
    }
}