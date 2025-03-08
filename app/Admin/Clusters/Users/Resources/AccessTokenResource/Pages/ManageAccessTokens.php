<?php

namespace App\Admin\Clusters\Users\Resources\AccessTokenResource\Pages;

use App\Admin\Clusters\Users\Resources\AccessTokenResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageAccessTokens extends ManageRecords
{
    protected static string $resource = AccessTokenResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->orderByDesc('last_used_at'))
            ->columns([
                TextColumn::make('tokenable.username')
                    ->label('用户'),
                TextColumn::make('name')
                    ->label('名称'),
                TextColumn::make('abilities')
                    ->label('权限'),
                TextColumn::make('last_used_at')
                    ->label('最后使用时间'),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
