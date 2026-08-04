<?php

namespace App\Models\Finance;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Models\Mall\Order;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\Finance\InvoiceApplicationPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(InvoiceApplicationPolicy::class)]
class InvoiceApplication extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => InvoiceApplicationStatus::class,
        ];
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

    /**
     * 关联发票
     *
     * @return HasOne<Invoice>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * 关联订单
     *
     * @return BelongsToMany<Order>
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'invoice_application_order')
            ->using(InvoiceApplicationOrder::class)
            ->withTimestamps();
    }
}
