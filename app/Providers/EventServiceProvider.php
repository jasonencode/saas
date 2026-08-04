<?php

namespace App\Providers;

use App\Events\Finance\InvoiceApplicationSubmitted;
use App\Events\Mall\OrderPaid;
use App\Listeners\Finance\SendInvoiceApplicationNotification;
use App\Listeners\User\GrantIdentityOnOrderPaid;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceApplicationSubmitted::class => [
            SendInvoiceApplicationNotification::class,
        ],
        OrderPaid::class => [
            GrantIdentityOnOrderPaid::class,
        ],
    ];
}
