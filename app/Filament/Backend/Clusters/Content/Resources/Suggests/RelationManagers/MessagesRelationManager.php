<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Suggests\RelationManagers;

use App\Filament\Actions\Content\ReplySuggestAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = '对话记录';

    protected static ?string $modelLabel = '消息';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('发送者')
                    ->placeholder('系统'),
                Tables\Columns\TextColumn::make('content')
                    ->label('内容')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                ReplySuggestAction::make(),
            ]);
    }
}
