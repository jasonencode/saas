<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Backend\Clusters\Contents\Resources\ContentResource;
use App\Models\Content;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class NoticeTable extends TableWidget
{
    protected static ?string $heading = '帮助文档';
    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(ContentResource::getEloquentQuery())
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->defaultPaginationPageOption(10)
            ->modelLabel('帮助文档')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('帮助文档')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('发布时间')
                    ->date(),
            ])
            ->actions([
                Tables\Actions\Action::make('views')
                    ->label('查看')
                    ->icon('heroicon-m-eye')
                    ->modal()
                    ->modalHeading(fn(Content $content) => $content->title)
                    ->modalContent(fn(Content $content) => new HtmlString($content->content))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('关闭'),
            ]);
    }
}
