<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tenants\RelationManagers;

use App\Filament\Backend\Clusters\Setting\Resources\Roles\Tables\RolesTable;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $modelLabel = '角色';

    protected static ?string $title = '角色管理';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
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

    public function table(Table $table): Table
    {
        return RolesTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
