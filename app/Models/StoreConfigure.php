<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasRegion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreConfigure extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasRegion;

    protected $primaryKey = 'tenant_id';

    public $incrementing = false;

    public function defaultExpress(): BelongsTo
    {
        return $this->belongsTo(Express::class, 'default_express_id')
            ->withoutGlobalScopes();
    }
}
