<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Mall\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return '订单详情';
    }

    public function getSubheading(): string
    {
        return $this->getRecord()->no;
    }
}
