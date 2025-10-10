<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Roles;

use App\Filament\Backend\Clusters\Settings\SettingsCluster;
use App\Models\AdminRole;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = AdminRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $modelLabel = '角色';

    protected static ?string $navigationLabel = '角色管理';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('角色名称')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('角色')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('角色名称')
                    ->description(fn(AdminRole $record) => $record->description)
                    ->searchable(),
                Tables\Columns\TextColumn::make('administrators_count')
                    ->counts('administrators')
                    ->label('角色人数'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
