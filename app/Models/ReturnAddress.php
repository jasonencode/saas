<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasRegion;
use App\Policies\ReturnAddressPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(ReturnAddressPolicy::class)]
class ReturnAddress extends Model
{
    use BelongsToTenant,
        HasRegion,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::saving(static function ($address) {
            if ($address->is_default && $address->tenant_id) {
                static::where('tenant_id', $address->tenant_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}