<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Payments\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->no;
    }
}
