<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\InvoicePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 发票模型
 */
#[Unguarded]
#[UsePolicy(InvoicePolicy::class)]
class Invoice extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    protected $casts = [
        'amount' => 'decimal:2',
        'invoice_date' => 'date',
        'type' => InvoiceType::class,
        'status' => InvoiceStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $invoice) {
            if ($invoice->tenant_id) {
                return;
            }

            if (!$invoice->user_id) {
                return;
            }

            $invoice->tenant_id = User::whereKey($invoice->user_id)
                ->value('tenant_id');
        });
    }

    /**
     * 关联发票申请
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(InvoiceApplication::class, 'invoice_application_id');
    }
}
