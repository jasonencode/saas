<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasRegion;
use App\Models\Traits\HasSortable;
use App\Policies\Mall\ReturnAddressPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(ReturnAddressPolicy::class)]
class ReturnAddress extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        HasRegion,
        HasSortable,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::saved(static function (self $address) {
            if ($address->is_default && $address->tenant_id) {
                static::where('tenant_id', $address->tenant_id)
                    ->where('id', '!=', $address->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}
