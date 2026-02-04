<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Sensitives;

use App\Enums\FilamentPanelGroup;
use App\Filament\Backend\Clusters\Content\ContentCluster;
use App\Models\Sensitive;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SensitiveResource extends Resource
{
    protected static ?string $model = Sensitive::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '敏感词';

    protected static ?string $navigationLabel = '敏感词管理';

    protected static ?int $navigationSort = 10;

    protected static string|null|UnitEnum $navigationGroup = FilamentPanelGroup::System;

    public static function form(Schema $schema): Schema
    {
        return Schemas\SensitiveForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SensitivesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSensitives::route('/'),
        ];
    }
}
