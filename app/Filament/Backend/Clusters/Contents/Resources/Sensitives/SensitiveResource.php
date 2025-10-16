<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Sensitives;

use App\Filament\Backend\Clusters\Contents\ContentsCluster;
use App\Models\Sensitive;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SensitiveResource extends Resource
{
    protected static ?string $model = Sensitive::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $cluster = ContentsCluster::class;

    protected static ?string $modelLabel = '敏感词';

    protected static ?string $navigationLabel = '敏感词管理';

    protected static ?int $navigationSort = 10;

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
