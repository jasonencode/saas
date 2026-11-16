<?php

namespace App\Filament\Tables\Content;

use App\Filament\Tables\Columns\UserInfoColumn;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge()
                    ->visible(fn (): bool => filament()->getTenant() === null),
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('content')
                    ->label('内容'),
                Tables\Columns\TextColumn::make('star')
                    ->label('星级'),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
