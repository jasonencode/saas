<?php

namespace App\Models\Finance;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\User;
use App\Policies\InvoiceApplicationPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 发票申请模型
 */
#[Unguarded]
#[UsePolicy(InvoiceApplicationPolicy::class)]
class InvoiceApplication extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    protected $casts = [
        'amount' => 'decimal:2',
        'order_ids' => 'json',
        'status' => InvoiceApplicationStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $application) {
            if ($application->tenant_id) {
                return;
            }

            if (!$application->user_id) {
                return;
            }

            $application->tenant_id = User::whereKey($application->user_id)
                ->value('tenant_id');
        });
    }

    /**
     * 关联发票抬头
     */
    public function invoiceTitle(): BelongsTo
    {
        return $this->belongsTo(InvoiceTitle::class);
    }

    /**
     * 关联发票
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
