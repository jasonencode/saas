<?php

namespace App\Filament\Backend\Clusters\Users\Resources;

use App\Filament\Backend\Clusters\Users;
use App\Filament\Backend\Clusters\Users\Resources\AccessTokenResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;

class AccessTokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static ?string $modelLabel = '用户凭证';

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-bangladeshi';

    protected static ?string $navigationLabel = '凭证管理';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Users::class;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->orderByDesc('last_used_at'))
            ->columns([
                Tables\Columns\TextColumn::make('tokenable.username')
                    ->label('用户')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('名称'),
                Tables\Columns\TextColumn::make('abilities')
                    ->label('权限'),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('最后使用时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAccessTokens::route('/'),
        ];
    }
}
