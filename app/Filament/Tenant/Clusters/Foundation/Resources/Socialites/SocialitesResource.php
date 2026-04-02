<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Socialites;

use App\Filament\Tenant\Clusters\Foundation\FoundationCluster;
use App\Models\Socialite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SocialitesResource extends Resource
{
    protected static ?string $model = Socialite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $cluster = FoundationCluster::class;

    protected static ?string $modelLabel = '社会化登录';

    protected static ?string $navigationLabel = '社会化登录';

    protected static ?int $navigationSort = 11;

    protected static string|UnitEnum|null $navigationGroup = '社会化登录';

    public static function table(Table $table): Table
    {
        return Tables\SocialitesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSocialites::route('/'),
        ];
    }
}
