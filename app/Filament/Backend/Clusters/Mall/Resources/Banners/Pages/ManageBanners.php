<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Banners\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Banners\BannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBanners extends ManageRecords
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
