<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Plans\RelationManagers;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Filament\Actions\Common\UpgradeSortAction;
use App\Models\Finance\Task;
use App\Services\Finance\TaskService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = '计划任务';

    protected static ?string $modelLabel = '计划任务';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('步骤名称')
                    ->required(),
                Forms\Components\Select::make('service')
                    ->label('挂载服务')
                    ->required()
                    ->live()
                    ->options(service(TaskService::class)->list())
                    ->afterStateUpdated(function (?string $state, Set $set) {
                        if ($state) {
                            $set('options', resolve($state)->getDefaultOptions());
                        } else {
                            $set('options', []);
                        }
                    }),
                Forms\Components\TextInput::make('sort')
                    ->label('执行顺序')
                    ->required()
                    ->default(fn () => Task::where('plan_id', $this->ownerRecord->getKey())->count() + 1)
                    ->integer(),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status'))
                    ->default(true),
                Forms\Components\KeyValue::make('options')
                    ->label('默认参数')
                    ->columnSpanFull()
                    ->helperText('默认参数是指挂载的服务，需要的一些默认参数，您可以改变这些参数的值来实现一些自定义的结果'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort(fn (Builder $query) => $query->bySort())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('步骤名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service')
                    ->label('挂载服务')
                    ->formatStateUsing(fn (?string $state) => resolve($state)->getTitle()),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
                Tables\Columns\TextColumn::make('sort')
                    ->label('执行顺序')
                    ->sortable(),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    UpgradeSortAction::make(),
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    EnableBulkAction::make(),
                    DisableBulkAction::make(),
                ]),
            ]);
    }
}
