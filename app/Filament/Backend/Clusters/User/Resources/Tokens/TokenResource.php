<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tokens;

use App\Filament\Backend\Clusters\User\UserCluster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;

class TokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = 'Token';

    protected static ?string $navigationLabel = '凭证管理';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return Tables\TokensTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTokens::route('/'),
        ];
    }
}
