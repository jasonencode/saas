<?php

namespace App\Events\Finance;

use App\Models\Finance\InvoiceApplication;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceApplicationSubmitted
{
    use Dispatchable,
        InteractsWithSockets,
        SerializesModels;

    public function __construct(public InvoiceApplication $application)
    {
        //
    }
}
