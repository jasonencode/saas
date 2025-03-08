<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Modules\Settlement\Models\Voucher;

trait HasSettlement
{
    public function vouchers(): MorphMany
    {
        return $this->morphMany(Voucher::class, 'target');
    }

    public function getSettlementTitleAttribute(): string
    {
        return Str::studly($this->getSettlementTitleField()).'#'.$this->no;
    }

    protected function getSettlementTitleField(): string
    {
        return $this->settlementTitleField ?? $this->getMorphClass();
    }
}