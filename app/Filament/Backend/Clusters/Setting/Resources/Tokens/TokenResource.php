<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Tokens;

use App\Filament\Backend\Clusters\Setting\Resources\Tokens\Pages\CreateToken;
use App\Filament\Backend\Clusters\Setting\Resources\Tokens\Pages\EditToken;
use App\Filament\Backend\Clusters\Setting\Resources\Tokens\Pages\ManageTokens;
use App\Filament\Backend\Clusters\Setting\Resources\Tokens\Schemas\TokenForm;
use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\Token;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;
use UnitEnum;

class TokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'API';

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
            'index' => ManageTokens::route('/'),
        ];
    }
}
