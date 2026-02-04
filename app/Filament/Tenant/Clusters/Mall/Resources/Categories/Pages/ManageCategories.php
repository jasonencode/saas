<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Categories\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;
}

