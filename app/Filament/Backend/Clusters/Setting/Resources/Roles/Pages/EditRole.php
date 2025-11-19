<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\Roles\RoleResource;
use App\Models\AdminRole;
use App\Models\AdminRolePermission;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('返回列表')
                ->icon(Heroicon::ArrowLeft)
                ->url(self::$resource::getUrl()),
            $this->getSaveFormAction()
                ->icon('heroicon-o-check-circle')
                ->label('保存编辑')
                ->formId('form'),
        ];
    }

    protected function handleRecordUpdate(AdminRole|Model $record, array $data): Model
    {
        $record->update(Arr::only($data, ['name', 'description']));
        $record->permissions()->delete();

        foreach ($data as $key => $items) {
            if (Str::startsWith($key, 'permissions')) {
                preg_match('/permissions\[(\S+)]/', $key, $match);
                if (class_exists($match[1])) {
                    foreach ($items as $item) {
                        $record->permissions()->create([
                            'role_id' => $record->id,
                            'policy' => $match[1],
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

            $data['permissions['.$permission['policy'].']'] = $methods;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }
}