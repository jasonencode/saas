<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Suggests\Tables;

use App\Enums\Content\SuggestStatus;
use App\Enums\Content\SuggestType;
use App\Filament\Actions\Content\ToggleSuggestCloseAction;
use App\Filament\Tables\Columns\UserInfoColumn;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class SuggestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('contact')
                    ->label('联系方式')
                    ->limit(20),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('消息数')
                    ->counts('messages'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('最后消息时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('提交时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型')
                    ->options(SuggestType::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options(SuggestStatus::class),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                    ToggleSuggestCloseAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
