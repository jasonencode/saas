<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tokens;

use App\Filament\Backend\Clusters\User\UserCluster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;
use UnitEnum;

class TokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '凭证';

    protected static ?string $navigationLabel = '凭证管理';

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = '用户';

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
