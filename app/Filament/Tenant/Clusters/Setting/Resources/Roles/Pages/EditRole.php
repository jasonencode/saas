<?php

namespace App\Filament\Tenant\Clusters\Setting\Resources\Roles\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\HeaderSubmitAction;
use App\Filament\Tenant\Clusters\Setting\Resources\Roles\RoleResource;
use App\Models\System\AdminRole;
use App\Models\System\AdminRolePermission;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            HeaderSubmitAction::make(),
        ];
    }

    protected function handleRecordUpdate(AdminRole|Model $record, array $data): Model
    {
        $record->update(Arr::only($data, ['name', 'description']));
        $record->permissions()->delete();

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            foreach ($data['permissions'] as $policy => $items) {
                if (class_exists($policy)) {
                    foreach ($items as $item) {
                        $record->permissions()->create([
                            'role_id' => $record->id,
                            'policy' => $policy,
                            'method' => $item,
                        ]);
                    }
                }
            }
        }

        return $record;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissions = AdminRolePermission::select('policy')
            ->distinct()
            ->where('role_id', $this->getRecord()->getKey())
            ->get();

        foreach ($permissions as $permission) {
            $methods = AdminRolePermission::where('role_id', $this->getRecord()->getKey())
                ->where('policy', $permission['policy'])
                ->select('method')
                ->pluck('method')
                ->toArray();

            $data['permissions'][$permission['policy']] = $methods;
        }

        return $data;
    }
}
