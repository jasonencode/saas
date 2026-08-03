<?php

namespace App\Filament\Actions\Tenant;

use App\Enums\System\AvailableModule;
use App\Models\System\Tenant;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Support\Icons\Heroicon;

class SetModulesAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'setModules';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('设置模块');
        $this->icon(Heroicon::OutlinedCog6Tooth);

        $this->visible(fn (Tenant $tenant): bool => userCan(self::getDefaultName(), $tenant));

        $this->requiresConfirmation();

        $this->modalHeading('设置可用模块');
        $this->modalDescription('选择该租户可以使用的模块，不选择则所有模块不可用。');

        $this->fillForm(function (Tenant $tenant): array {
            return [
                'modules' => $tenant->getModules(),
            ];
        });

        $this->schema([
            CheckboxList::make('modules')
                ->label('可用模块')
                ->options(AvailableModule::class)
                ->bulkToggleable()
                ->nullable()
                ->columns(),
        ]);

        $this->action(function (Tenant $tenant, array $data): void {
            $modules = $data['modules'] ?? [];
            $tenant->update([
                'config' => array_merge($tenant->config ?? [], ['modules' => $modules]),
            ]);
            $this->successNotificationTitle('模块设置已更新');
            $this->success();
        });
    }
}
