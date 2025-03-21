<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\TenantResource\RelationManagers;

use App\Models\AdminRole;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $modelLabel = '角色';

    protected static ?string $title = '团队角色';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('角色名称')
                    ->required(),
                Textarea::make('description')
                    ->label('描述')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->orderByDesc('is_sys')->latest())
            ->columns([
                TextColumn::make('name')
                    ->label('角色名称')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('描述')
                    ->searchable(),
                IconColumn::make('is_sys')
                    ->label('系统角色'),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn(AdminRole $record) => $record->is_sys),
            ]);
    }
}
