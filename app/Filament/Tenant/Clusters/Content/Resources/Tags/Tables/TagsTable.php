<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\Tags\Tables;

use App\Enums\Content\TagType;
use App\Filament\Actions\Common\UpgradeSortAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('type', TagType::Content))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('标签名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contents_count')
                    ->label('内容数量')
                    ->counts('contents')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    UpgradeSortAction::make(),
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ]);
    }
}
