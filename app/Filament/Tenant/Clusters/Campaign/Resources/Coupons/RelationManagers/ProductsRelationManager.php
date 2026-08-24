<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\RelationManagers;

use App\Models\Mall\Product;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = '关联商品';

    protected static ?string $modelLabel = '商品';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('封面图'),
                Tables\Columns\TextColumn::make('name')
                    ->label('商品名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('价格')
                    ->money('cny'),
            ])
            ->headerActions([
                Actions\Action::make('attach')
                    ->label('添加商品')
                    ->schema([
                        Select::make('product_ids')
                            ->label('选择商品')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => $this->searchProducts($search))
                            ->getOptionLabelUsing(fn ($value): string => Product::select('name')->find($value)?->name ?? (string) $value)
                            ->getOptionLabelsUsing(fn (array $values): array => Product::select('id', 'name')->whereIn('id', $values)->pluck('name', 'id')->all())
                            ->required(),
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
                Actions\DetachAction::make(),
            ])
            ->toolbarActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function searchProducts(string $search): array
    {
        $couponId = $this->getOwnerRecord()->getKey();
        $tenantId = Filament::getTenant()?->getKey();

        return Product::query()
            ->select(['id', 'name'])
            ->where('tenant_id', $tenantId)
            ->whereDoesntHave('coupons', fn ($q) => $q->where('coupons.id', $couponId))
            ->where('name', 'like', "%{$search}%")
            ->limit(50)
            ->pluck('name', 'id')
            ->all();
    }
}
