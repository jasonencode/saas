<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\HeaderSubmitAction;
use App\Filament\Backend\Clusters\Setting\Resources\Roles\RoleResource;
use App\Models\System\AdminRole;
use App\Models\System\AdminRolePermission;
use App\Services\System\EventLogService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->assertRoleIsEditable();
    }

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            HeaderSubmitAction::make(),
        ];
    }

    protected function handleRecordUpdate(AdminRole|Model $record, array $data): Model
    {
        $this->assertRoleIsEditable();

        DB::transaction(function () use ($record, $data): void {
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
        });

        service(EventLogService::class)->write(
            'system.role_permissions_updated',
            '更新角色权限',
            auth()->user(),
            $record,
            null,
            ['name' => $record->name],
        );

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

    /**
     * 系统角色与当前操作者所属角色不可编辑
     */
    private function assertRoleIsEditable(): void
    {
        $role = $this->getRecord();

        abort_if($role->is_sys, 403, '系统角色不可编辑');
        abort_if(
            $role->administrators()->whereKey(auth()->id())->exists(),
            403,
            '不能编辑自己所属的角色'
        );
    }
}
