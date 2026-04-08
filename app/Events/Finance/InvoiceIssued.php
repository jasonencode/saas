<?php

namespace App\Events\Finance;

use App\Models\Finance\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceIssued
{
    use Dispatchable,
        SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }
}