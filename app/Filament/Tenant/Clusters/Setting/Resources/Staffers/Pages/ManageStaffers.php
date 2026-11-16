<?php

namespace App\Filament\Tenant\Clusters\Setting\Resources\Staffers\Pages;

use App\Enums\System\AdminType;
use App\Filament\Tenant\Clusters\Setting\Resources\Staffers\StafferResource;
use App\Models\System\Administrator;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffers extends ManageRecords
{
    protected static string $resource = StafferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                // 强制租户类型并挂到当前租户：否则员工既进不了租户面板，也会落进后端管理员集合
                ->using(function (array $data): Administrator {
                    $data['type'] = AdminType::Tenant;

                    $staffer = Administrator::create($data);
                    $staffer->tenants()->attach(Filament::getTenant());

                    return $staffer;
                }),
        ];
    }
}
