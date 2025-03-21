<?php

namespace App\Filament\Backend\Clusters\Settings\Resources;

use App\Contracts\InstallableModule;
use App\Extensions\Module\InstallOption;
use App\Extensions\Module\ModuleInstall;
use App\Filament\Backend\Clusters\Settings;
use App\Filament\Backend\Clusters\Settings\Resources\ModuleResource\Pages\ManageModules;
use App\Models\Module;
use Exception;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static ?string $modelLabel = '模块';

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = '模块管理';

    protected static ?string $navigationGroup = '模块';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 100;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->orderByDesc('status'))
            ->columns([
                TextColumn::make('name')
                    ->label('标识')
                    ->searchable(),
                TextColumn::make('alias')
                    ->label('名称')
                    ->searchable(),
                TextColumn::make('author')
                    ->label('作者')
                    ->searchable(),
                TextColumn::make('version')
                    ->label('版本'),
                TextColumn::make('description')
                    ->label('描述'),
                IconColumn::make('status')
                    ->label('状态'),
            ])
            ->actions([
                self::disableButton(),
                self::enableButton(),
                DeleteAction::make()
                    ->visible(fn(Module $record) => !$record->status),
            ])
            ->bulkActions([
            ]);
    }

    protected static function disableButton()
    {
        return Action::make('disable')
            ->label('卸载')
            ->modalHeading(fn(Module $record) => '卸载【'.$record->alias.'】')
            ->color('warning')
            ->icon('heroicon-o-x-mark')
            ->requiresConfirmation()
            ->databaseTransaction(false)
            ->form([
                Fieldset::make('卸载选项')
                    ->columns(1)
                    ->schema([
                        Toggle::make('remove_database')
                            ->label('移除数据库')
                            ->default(true),
                    ]),
                TextInput::make('password')
                    ->label('当前密码')
                    ->password()
                    ->required()
                    ->currentPassword(),
            ])
            ->visible(fn(Module $record) => userCan('uninstall', self::getModel()) && $record->status)
            ->action(function(array $data, Module $record, Action $action) {
                try {
                    Artisan::call('module:disable', [
                        'module' => $record->name,
                    ]);
                    if (filament($record->module_id) instanceof InstallableModule) {
                        filament($record->module_id)->uninstall(new InstallOption($data));
                    }
                    $action->successNotificationTitle('卸载成功');
                    $action->success();
                } catch (Exception $exception) {
                    $action->failureNotificationTitle($exception->getMessage());
                    $action->failure();
                }
            });
    }

    protected static function enableButton()
    {
        return Action::make('enable')
            ->label('安装')
            ->modalHeading(fn(Module $record) => '安装【'.$record->alias.'】')
            ->color('success')
            ->icon('heroicon-o-check')
            ->requiresConfirmation()
            ->databaseTransaction(false)
            ->form([
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
            ->visible(fn(Module $record) => userCan('install', self::getModel()) && !$record->status)
            ->action(function(array $data, Module $record, Action $action) {
                try {
                    Artisan::call('module:enable', [
                        'module' => $record->name,
                    ]);
                    ModuleInstall::install($record->name, new InstallOption($data));
                    $action->successNotificationTitle('安装成功');
                    $action->success();
                } catch (Exception $exception) {
                    $action->failureNotificationTitle($exception->getMessage());
                    $action->failure();
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageModules::route('/'),
        ];
    }
}
