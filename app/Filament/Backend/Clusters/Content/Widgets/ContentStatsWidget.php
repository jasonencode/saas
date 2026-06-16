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
use Illuminate\Support\Facades\Cache;

class ContentStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $cacheKey = 'content_stats_widget';
        $cacheTtl = 60;

        $data = Cache::remember($cacheKey, $cacheTtl, static function () {
            $today = Carbon::today();
            $weekAgo = $today->subDays(7);

            return [
                'total_contents' => Content::count(),
                'published_contents' => Content::where('status', true)->count(),
                'draft_contents' => Content::where('status', false)->count(),
                'today_contents' => Content::whereDate('created_at', $today)->count(),
                'week_contents' => Content::whereDate('created_at', '>=', $weekAgo)->count(),
                'total_comments' => Comment::count(),
                'approved_comments' => Comment::where('status', true)->count(),
                'pending_comments' => Comment::where('status', false)->count(),
                'today_comments' => Comment::whereDate('created_at', $today)->count(),
                'week_comments' => Comment::whereDate('created_at', '>=', $weekAgo)->count(),
                'total_categories' => ContentCategory::count(),
                'total_versions' => AppVersion::count(),
                'version_platforms' => AppVersion::distinct('platform')->count(),
            ];
        });

        return [
            Stat::make('内容总数', $data['total_contents'])
                ->description("已发布：{$data['published_contents']} / 草稿：{$data['draft_contents']}")
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('primary')
                ->url(ContentResource::getUrl()),

            Stat::make('今日发布', $data['today_contents'])
                ->description("近7天：{$data['week_contents']} 篇")
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('success')
                ->url(ContentResource::getUrl()),

            Stat::make('评论总数', $data['total_comments'])
                ->description("已审核：{$data['approved_comments']} / 待审核：{$data['pending_comments']}")
                ->descriptionIcon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('info')
                ->url(CommentResource::getUrl()),

            Stat::make('今日评论', $data['today_comments'])
                ->description("近7天：{$data['week_comments']} 条")
                ->descriptionIcon(Heroicon::OutlinedChatBubbleOvalLeftEllipsis)
                ->color('gray')
                ->url(CommentResource::getUrl()),

            Stat::make('分类数量', $data['total_categories'])
                ->description('内容分类体系')
                ->descriptionIcon(Heroicon::OutlinedTag)
                ->color('success')
                ->url(CategoryResource::getUrl()),

            Stat::make('版本发布', $data['total_versions'])
                ->description("覆盖 {$data['version_platforms']} 个平台")
                ->descriptionIcon(Heroicon::OutlinedArrowUpCircle)
                ->color('warning')
                ->url(AppVersionResource::getUrl()),
        ];
    }
}
