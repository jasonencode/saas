<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasRegion;
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
        SoftDeletes;

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::saving(static function ($address) {
            // 仅当 is_default 从 false 变为 true 时才重置其他记录
            if ($address->is_default
                && $address->tenant_id
                && $address->isDirty('is_default')
            ) {
                static::where('tenant_id', $address->tenant_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
