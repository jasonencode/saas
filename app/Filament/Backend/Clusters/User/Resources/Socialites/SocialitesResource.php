<?php

namespace App\Filament\Backend\Clusters\User\Resources\Socialites;

use App\Enums\FilamentPanelGroup;
use App\Filament\Backend\Clusters\User\UserCluster;
use App\Models\Socialite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SocialitesResource extends Resource
{
    protected static ?string $model = Socialite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '社会化登录';

    protected static ?string $navigationLabel = '社会化登录';

    protected static ?int $navigationSort = 50;

    protected static string|UnitEnum|null $navigationGroup = FilamentPanelGroup::Socialite;

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
