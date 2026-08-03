<?php

namespace App\Filament\Tenant\Clusters\Content\Widgets;

use App\Filament\Tenant\Clusters\Content\ContentCluster;
use App\Filament\Tenant\Clusters\Content\Resources\Categories\CategoryResource;
use App\Filament\Tenant\Clusters\Content\Resources\Contents\ContentResource;
use App\Models\Content\Content;
use App\Models\Content\ContentCategory;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return ContentCluster::canAccess();
    }

    protected function getStats(): array
    {
        return [
            Stat::make('内容总数', Content::count())
                ->description('已发布：'.Content::where('status', true)->count().' / 草稿：'.Content::where('status', false)->count())
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('primary')
                ->url(ContentResource::getIndexUrl()),

            Stat::make('今日发布', Content::whereDate('created_at', Carbon::today())->count())
                ->description('近7天：'.Content::whereDate('created_at', '>=', Carbon::today()->subDays(7))->count().' 篇')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('success')
                ->url(ContentResource::getIndexUrl()),

            Stat::make('分类数量', ContentCategory::count())
                ->description('内容分类体系')
                ->descriptionIcon(Heroicon::OutlinedTag)
                ->color('success')
                ->url(CategoryResource::getIndexUrl()),
        ];
    }
}
