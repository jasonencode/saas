<?php

namespace App\Models\Finance;

use App\Enums\Finance\InvoiceTitleType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\User\User;
use App\Policies\Finance\InvoiceTitlePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(InvoiceTitlePolicy::class)]
class InvoiceTitle extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    protected $casts = [
        'type' => InvoiceTitleType::class,
        'is_default' => 'bool',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $title) {
            if ($title->tenant_id) {
                return;
            }

            if (!$title->user_id) {
                return;
            }

            $title->tenant_id = User::whereKey($title->user_id)
                ->value('tenant_id');
        });

        static::saved(static function (self $title) {
            if (!$title->is_default) {
                return;
            }

            self::where('user_id', $title->user_id)
                ->whereKeyNot($title->getKey())
                ->update(['is_default' => false]);
        });
    }

    public function setDefault(): bool
    {
        $this->is_default = true;

        return $this->save();
    }
}
