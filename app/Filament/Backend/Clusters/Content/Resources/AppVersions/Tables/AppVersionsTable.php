<?php

namespace App\Filament\Backend\Clusters\Content\Resources\AppVersions\Tables;

use App\Enums\PlatformType;
use App\Filament\Actions\Content\PublishNowAction;
use App\Filament\Actions\Content\SchedulePublishAction;
use App\Filament\Actions\Content\UnpublishAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class AppVersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('platform')
                    ->label('平台')
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_id')
                    ->label('包名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('version')
                    ->label('版本号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('force')
                    ->label('强制更新'),
                Tables\Columns\TextColumn::make('status')
                    ->label('发布状态')
                    ->state(function ($record) {
                        if (filled($record->publish_at) && $record->publish_at->isFuture()) {
                            return '计划发布';
                        }
                        if (filled($record->publish_at) && $record->publish_at->isPast()) {
                            return '已发布';
                        }

                        return '未发布';
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            '已发布' => 'success',
                            '计划发布' => 'warning',
                            default => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('download_url')
                    ->label('下载地址')
                    ->url(fn($record) => $record->download_url, true)
                    ->copyable(),
                Tables\Columns\TextColumn::make('publish_at')
                    ->label('发布时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->label('平台')
                    ->options(PlatformType::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label('发布状态')
                    ->options([
                        'published' => '已发布',
                        'scheduled' => '计划发布',
                        'draft' => '未发布',
                    ])
                    ->query(function ($query, $state) {
                        return match ($state) {
                            'published' => $query->whereNotNull('publish_at')->where('publish_at', '<=', now()),
                            'scheduled' => $query->whereNotNull('publish_at')->where('publish_at', '>', now()),
                            'draft' => $query->whereNull('publish_at'),
                            default => $query,
                        };
                    }),
                Tables\Filters\TernaryFilter::make('force')
                    ->label('强制更新')
                    ->nullable(),
            ])
            ->recordActions([
                PublishNowAction::make(),
                SchedulePublishAction::make(),
                UnpublishAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
