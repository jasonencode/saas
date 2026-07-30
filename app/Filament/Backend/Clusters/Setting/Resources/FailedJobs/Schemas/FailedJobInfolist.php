<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\FailedJobs\Schemas;

use App\Filament\Infolists\Components\TextareaEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FailedJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本信息')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('uuid')
                            ->label('UUID')
                            ->copyable(),
                        TextEntry::make('connection')
                            ->label('连接'),
                        TextEntry::make('queue')
                            ->label('队列')
                            ->badge(),
                        TextEntry::make('failed_at')
                            ->label('失败时间')
                            ->placeholder('-'),
                    ]),
                Section::make('任务信息')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->schema([
                        TextEntry::make('payload')
                            ->label('任务名称')
                            ->formatStateUsing(fn ($state): ?string => json_decode($state, true, 512, JSON_THROW_ON_ERROR)['displayName'] ?? null)
                            ->placeholder('-'),
                        TextareaEntry::make('payload')
                            ->label('任务载荷')
                            ->rows(10),
                    ]),
                Section::make('异常信息')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->schema([
                        TextEntry::make('exception')
                            ->label('异常堆栈')
                            ->html()
                            ->formatStateUsing(fn (string $state): string => '<div style="line-height: 2">'.nl2br(e($state)).'</div>')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
