<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Payment\Models\Payment;

interface ShouldPayment
{
    public function payments(): MorphMany;

    public function getPaymentTitleAttribute(): string;

    public function paid(Payment $payment): bool;

    public function canPay(): bool;

    public function canRefund(): bool;

    public function getTotalAmount(): string;

    public function user(): BelongsTo;
}
