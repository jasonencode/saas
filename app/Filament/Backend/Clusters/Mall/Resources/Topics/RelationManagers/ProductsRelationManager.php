<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Topics\RelationManagers;

use App\Filament\Actions\Common\UpgradePivotSortAction;
use App\Filament\Tables\TopicProductsTable;
use App\Models\Mall\Product;
use Filament\Actions;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = '专题商品';

    protected static ?string $modelLabel = '商品';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')
                    ->label('封面图')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->label('商品名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('销售价')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('skus_count')
                    ->label('SKU 数量')
                    ->counts('skus')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.sort')
                    ->label('排序')
                    ->sortable(),
            ])
            ->headerActions([
                Actions\Action::make('attach')
                    ->label('添加商品')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Fieldset::make('选择商品')
                            ->schema([
                                ModalTableSelect::make('product_ids')
                                    ->columnSpanFull()
                                    ->hiddenLabel()
                                    ->multiple()
                                    ->required()
                                    ->tableConfiguration(TopicProductsTable::class)
                                    ->tableArguments(fn (): array => [
                                        'tenant_id' => $this->getOwnerRecord()->tenant_id,
                                        'topic_id' => $this->getOwnerRecord()->getKey(),
                                    ])
                                    ->getOptionLabelsUsing(fn (array $values): array => Product::query()
                                        ->whereIn('id', $values)
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->selectAction(fn (Actions\Action $action) => $action
                                        ->label('选择商品')
                                        ->modalHeading('选择商品')
                                        ->modalSubmitActionLabel('确认添加')),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $productIds = $data['product_ids'] ?? [];

                        if (!empty($productIds)) {
                            $this->getOwnerRecord()->products()->syncWithoutDetaching($productIds);

                            Notification::make()
                                ->title('添加成功')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                UpgradePivotSortAction::make(),
                Actions\DetachAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
