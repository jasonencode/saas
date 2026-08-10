<?php

namespace App\Models\Finance;

use App\Models\Mall\Order;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Unguarded]
class InvoiceApplicationOrder extends Pivot
{
    /**
     * 关联订单
     *
     * @return BelongsTo<Order>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 关联发票申请
     *
     * @return BelongsTo<InvoiceApplication>
     */
    public function invoiceApplication(): BelongsTo
    {
        return $this->belongsTo(InvoiceApplication::class);
    }
}
