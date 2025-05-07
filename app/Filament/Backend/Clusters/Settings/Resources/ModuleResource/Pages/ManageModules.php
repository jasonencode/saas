<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\ModuleResource\Pages;

use App\Extensions\Module\InstallOption;
use App\Extensions\Module\ModuleInstall;
use App\Filament\Backend\Clusters\Settings\Resources\ModuleResource;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Facades\Module;

class ManageModules extends ManageRecords
{
    protected static string $resource = ModuleResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('cache')
                ->label('清理缓存')
                ->action(function(Action $action) {
                    Artisan::call('modelCache:clear');

                    foreach (Filament::getPanels() as $panel) {
                        $panel->clearCachedComponents();
                    }
                    $action->successNotificationTitle('清理成功');
                    $action->success();
                }),
            Action::make('install')
                ->label('安装模块')
                ->icon('heroicon-o-inbox-arrow-down')
                ->requiresConfirmation()
                ->databaseTransaction(false)
                ->form([
                    Select::make('module')
                        ->label('选择模块')
                        ->native(false)
                        ->options(function() {
                            return collect(Module::allDisabled())->map(function($module) {
                                return $module->get('alias');
                            });
                        })
                        ->required(),
                    Fieldset::make('安装选项')
                        ->columns(1)
                        ->schema([
                            Toggle::make('migrate_database')
                                ->label('执行数据库迁移')
                                ->default(true),
                            Toggle::make('database_seed')
                                ->label('执行数据初始化')
                                ->default(true),
                        ]),
                    TextInput::make('password')
                        ->label('当前密码')
                        ->password()
                        ->required()
                        ->currentPassword(),
                ])
                ->action(function(array $data, Action $action) {
                    try {
                        Artisan::call('module:enable', [
                            'module' => $data['module'],
                        ]);
                        ModuleInstall::install($data['module'], new InstallOption($data));
                        $action->successNotificationTitle('安装成功');
                        $action->success();
                    } catch (Exception $exception) {
                        $action->failureNotificationTitle($exception->getMessage());
                        $action->failure();
                    }
                }),
        ];
    }
}
