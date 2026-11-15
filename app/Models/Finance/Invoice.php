<?php

namespace App\Models\Finance;

use App\Enums\Finance\InvoiceStatus;
use App\Enums\Finance\InvoiceType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\Finance\InvoicePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(InvoicePolicy::class)]
class Invoice extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'invoice_date' => 'date',
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'title_snapshot' => 'json',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (self $invoice) {
            if ($invoice->tenant_id || !$invoice->invoice_application_id) {
                return;
            }

            // 租户取自发票申请，不查 users（users 表没有 tenant_id 列）
            $invoice->tenant_id = InvoiceApplication::whereKey($invoice->invoice_application_id)
                ->value('tenant_id');
        });
    }

    /**
     * 关联发票申请
     *
     * @return BelongsTo<InvoiceApplication>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(InvoiceApplication::class, 'invoice_application_id');
    }

    /**
     * 关联发票抬头
     *
     * @return BelongsTo<InvoiceTitle>
     */
    public function invoiceTitle(): BelongsTo
    {
        return $this->belongsTo(InvoiceTitle::class);
    }
}
