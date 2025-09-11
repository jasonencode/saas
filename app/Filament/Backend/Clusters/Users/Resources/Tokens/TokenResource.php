<?php

namespace App\Filament\Backend\Clusters\Users\Resources\Tokens;

use App\Filament\Backend\Clusters\Users\Resources\Tokens\Pages\ManageTokens;
use App\Filament\Backend\Clusters\Users\UsersCluster;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;

class TokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UsersCluster::class;

    protected static ?string $modelLabel = 'Token';

    protected static ?string $navigationLabel = '凭证管理';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->orderByDesc('last_used_at'))
            ->columns([
                Tables\Columns\TextColumn::make('tokenable.info.nickname')
                    ->label('用户')
                    ->description(fn(PersonalAccessToken $record) => $record->tokenable?->username)
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('名称'),
                Tables\Columns\TextColumn::make('abilities')
                    ->label('权限'),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('最后使用时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTokens::route('/'),
        ];
    }
}
