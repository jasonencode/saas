<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use BelongsToTenant,
        Cachable,
        HasCovers,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;

    protected $table = 'mall_brands';

    protected $casts = [
        'ext' => 'json',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
