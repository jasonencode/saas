<?php

namespace App\Filament\Backend\Clusters\Content\Widgets;

use App\Filament\Backend\Clusters\Content\Resources\AppVersions\AppVersionResource;
use App\Filament\Backend\Clusters\Content\Resources\Categories\CategoryResource;
use App\Filament\Backend\Clusters\Content\Resources\Comments\CommentResource;
use App\Filament\Backend\Clusters\Content\Resources\Contents\ContentResource;
use App\Models\Content\AppVersion;
use App\Models\Content\Comment;
use App\Models\Content\Content;
use App\Models\Content\ContentCategory;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('内容总数', Content::count())
                ->description('已发布：'.Content::where('status', true)->count().' / 草稿：'.Content::where('status', false)->count())
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('primary')
                ->url(ContentResource::getUrl()),

            Stat::make('今日发布', Content::whereDate('created_at', Carbon::today())->count())
                ->description('近7天：'.Content::whereDate('created_at', '>=', Carbon::today()->subDays(7))->count().' 篇')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('success')
                ->url(ContentResource::getUrl()),

            Stat::make('评论总数', Comment::count())
                ->description('已审核：'.Comment::where('status', true)->count().' / 待审核：'.Comment::where('status', false)->count())
                ->descriptionIcon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('info')
                ->url(CommentResource::getUrl()),

            Stat::make('今日评论', Comment::whereDate('created_at', Carbon::today())->count())
                ->description('近7天：'.Comment::whereDate('created_at', '>=', Carbon::today()->subDays(7))->count().' 条')
                ->descriptionIcon(Heroicon::OutlinedChatBubbleOvalLeftEllipsis)
                ->color('gray')
                ->url(CommentResource::getUrl()),

            Stat::make('分类数量', ContentCategory::count())
                ->description('内容分类体系')
                ->descriptionIcon(Heroicon::OutlinedTag)
                ->color('success')
                ->url(CategoryResource::getUrl()),

            Stat::make('版本发布', AppVersion::count())
                ->description('覆盖 '.AppVersion::distinct('platform')->count().' 个平台')
                ->descriptionIcon(Heroicon::OutlinedArrowUpCircle)
                ->color('warning')
                ->url(AppVersionResource::getUrl()),
        ];
    }
}
