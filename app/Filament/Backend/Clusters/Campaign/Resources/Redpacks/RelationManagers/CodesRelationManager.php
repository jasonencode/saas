<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\RelationManagers;

use App\Enums\RedpackCodeStatus;
use App\Filament\Actions\Campaign\CreateCodeBulkAction;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CodesRelationManager extends RelationManager
{
    protected static string $relationship = 'codes';

    protected static ?string $title = '红包码';

    protected static ?string $modelLabel = '红包码';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('amount')
                    ->label('金额')
                    ->required()
                    ->numeric()
                    ->prefix('￥')
                    ->suffix('元'),
                Forms\Components\Select::make('status')
                    ->label(__('backend.status'))
                    ->options(RedpackCodeStatus::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('红包码')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('金额')
                    ->money('CNY'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('领取用户'),
                Tables\Columns\TextColumn::make('claimed_ip')
                    ->label('领取IP'),
                Tables\Columns\TextColumn::make('claimed_at')
                    ->label('领取时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(RedpackCodeStatus::class),
            ])
            ->headerActions([
                CreateCodeBulkAction::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
