<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn(Builder $query) => $query->bySort())
            ->columns([
                ImageColumn::make('cover')
                    ->label('封面图'),
                TextColumn::make('name')
                    ->label('商品名称')
                    ->searchable(),
                TextColumn::make('tenant.name')
                    ->label('店铺名称')
                    ->searchable(),
                TextColumn::make('categories.name')
                    ->label('分类')
                    ->badge()
                    ->searchable(),
                TextColumn::make('brand.name')
                    ->label('品牌名称')
                    ->searchable(),
                TextColumn::make('stocks')
                    ->label('库存'),
                TextColumn::make('sales')
                    ->label('销量'),
                TextColumn::make('views')
                    ->label('浏览')
                    ->action(
                        Action::make('views')
                            ->requiresConfirmation()
                            ->modalHeading('修改浏览量')
                            ->fillForm(function (Product $record) {
                                return ['views' => $record->views];
                            })
                            ->schema([
                                TextInput::make('views')
                                    ->label('浏览量')
                                    ->required()
                                    ->integer()
                                    ->autofocus(false),
                            ])
                            ->action(function (array $data, Product $record, Action $action) {
                                $record->views = $data['views'];
                                $record->save();
                                $action->successNotificationTitle('操作成功');
                                $action->success();
                            })
                    ),
                TextColumn::make('sort')
                    ->label('排序'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

