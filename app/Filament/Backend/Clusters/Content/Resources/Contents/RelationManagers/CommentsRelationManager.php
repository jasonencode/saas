<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Contents\RelationManagers;

use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $modelLabel = '评论';

    protected static ?string $title = '评论';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('评论用户')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'username',
                    )
                    ->required()
                    ->preload(),
                Forms\Components\Toggle::make('status')
                    ->label('状态'),
                Forms\Components\Slider::make('star')
                    ->label('评分')
                    ->range(minValue: 1, maxValue: 5)
                    ->step(1)
                    ->tooltips()
                    ->fillTrack()
                    ->required(),
                CustomUpload::pictures('pictures', '评论图片'),
                Forms\Components\Textarea::make('content')
                    ->label('评论内容')
                    ->rows(5)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                UserInfoColumn::make(),
                Tables\Columns\ImageColumn::make('pictures')
                    ->imageHeight(40)
                    ->circular()
                    ->stacked()
                    ->limit(4)
                    ->limitedRemainingText(),
                Tables\Columns\TextColumn::make('star')
                    ->label('评分'),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\RestoreAction::make(),
                Actions\ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}