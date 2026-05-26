<?php

namespace App\Filament\Backend\Widgets;

use App\Filament\Backend\Clusters\Content\Resources\Contents\ContentResource;
use App\Models\Content\AppVersion;
use App\Models\Content\Category;
use App\Models\Content\Comment;
use App\Models\Content\Content;
use App\Models\Content\ContentCategory;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;

class ContentOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            StatsOverviewWidget\Stat::make('内容总数', Content::count())
                ->description('已发布：'.Content::where('status', true)->count())
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('primary')
                ->url(ContentResource::getUrl()),

            StatsOverviewWidget\Stat::make('评论总数', Comment::count())
                ->description('今日新增：'.Comment::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('info'),

            StatsOverviewWidget\Stat::make('分类数量', ContentCategory::count())
                ->description('内容分类体系')
                ->descriptionIcon(Heroicon::OutlinedTag)
                ->color('success'),

            StatsOverviewWidget\Stat::make('版本发布', AppVersion::count())
                ->description('覆盖 '.AppVersion::distinct('platform')->count().' 个平台')
                ->descriptionIcon(Heroicon::OutlinedArrowUpCircle)
                ->color('warning'),
        ];
    }
}
