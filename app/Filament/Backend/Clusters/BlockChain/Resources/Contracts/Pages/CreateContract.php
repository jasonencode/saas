<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\Pages;

use App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\ContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;
}
