<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Modules\Payment\Models\Payment;

trait HasPayment
{
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'target');
    }

    public function getPaymentTitleAttribute(): string
    {
        return Str::studly($this->getPaymentTitleField()).'#'.$this->no;
    }

    protected function getPaymentTitleField(): string
    {
        return $this->paymentTitleField ?? $this->getMorphClass();
    }
}