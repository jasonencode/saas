<?php

namespace App\Filament\Actions\User;

use App\Models\System\Tenant;
use App\Models\User\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class AuthorizeTenantAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'authorizeTenant';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('授权租户');
        $this->icon(Heroicon::OutlinedBuildingOffice);
        $this->modalWidth(Width::Medium);

        $this->visible(fn (User $record): bool => userCan(self::getDefaultName(), $record));

        $this->schema([
            Forms\Components\Select::make('tenant_ids')
                ->label(__('backend.tenant'))
                ->multiple()
                ->options(fn (User $record) => Tenant::ofEnabled()->pluck('name', 'id'))
                ->default(fn (User $record) => $record->tenants->pluck('id')),
        ]);

        $this->action(function (User $record, array $data): void {
            $record->tenants()->sync($data['tenant_ids']);

            $this->successNotificationTitle('租户授权成功');
            $this->success();
        });
    }
}
