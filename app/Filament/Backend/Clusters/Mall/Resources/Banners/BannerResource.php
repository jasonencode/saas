<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Banners;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\Mall\Banner;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '轮播图';

    protected static ?string $navigationLabel = '轮播图管理';

    protected static string|null|UnitEnum $navigationGroup = '基础配置';

    protected static ?int $navigationSort = 32;

    public static function form(Schema $schema): Schema
    {
        return Schemas\BannerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\BannersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBanners::route('/'),
        ];
    }
}
