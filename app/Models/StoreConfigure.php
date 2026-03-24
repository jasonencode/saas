<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasRegion;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[Table(key: 'tenant_id', incrementing: false)]
class StoreConfigure extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasRegion;

    public function defaultExpress(): BelongsTo
    {
        return $this->belongsTo(Express::class, 'default_express_id')
            ->withoutGlobalScopes();
    }
}
