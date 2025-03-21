<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\TenantResource\RelationManagers;

use App\Models\AdminRole;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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
                Tables\Columns\TextColumn::make('name')
                    ->label('角色名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('描述')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_sys')
                    ->label('系统角色'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(AdminRole $record) => $record->is_sys),
            ]);
    }
}
