<?php

namespace App\Filament\Tables;

use App\Models\Mall\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TopicProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Product::query())
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {
                $arguments = $table->getArguments();

                if ($tenantId = $arguments['tenant_id'] ?? null) {
                    $query->where('tenant_id', $tenantId);
                }

                if ($topicId = $arguments['topic_id'] ?? null) {
                    $query->whereDoesntHave('topics', fn (Builder $q) => $q->where('topics.id', $topicId));
                }

                return $query;
            })
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')
                    ->label('封面图')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->label('商品名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('origin_price')
                    ->label('原价')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('price')
                    ->label('销售价')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('skus_count')
                    ->label('SKU 数量')
                    ->counts('skus')
                    ->sortable(),
            ]);
    }
}
