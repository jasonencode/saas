<?php

namespace App\Providers;

use App\Events\Finance\InvoiceApplicationSubmitted;
use App\Listeners\Finance\SendInvoiceApplicationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceApplicationSubmitted::class => [
            SendInvoiceApplicationNotification::class,
        ],
    ];

    public function boot()
    {
        //
    }
}
