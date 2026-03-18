<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface PayableInterface
{
    public function paymentOrder(): HasMany;
}