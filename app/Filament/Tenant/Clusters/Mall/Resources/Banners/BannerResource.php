<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Banners;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Filament\Tenant\Clusters\Mall\Resources\Banners\Pages\ManageBanners;
use App\Filament\Tenant\Clusters\Mall\Resources\Banners\Schemas\BannerForm;
use App\Filament\Tenant\Clusters\Mall\Resources\Banners\Tables\BannersTable;
use App\Models\Banner;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
        return BannerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BannersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBanners::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
