<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Identities\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IdentitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->bySort())
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('图标')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('身份名称')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('订阅价格')
                    ->prefix('¥')
                    ->sortable(),
                Tables\Columns\TextColumn::make('days')
                    ->label('有效期')
                    ->formatStateUsing(fn ($state) => $state === 0 ? '永久' : $state.'天')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('默认')
                    ->boolean(),
                Tables\Columns\IconColumn::make('can_subscribe')
                    ->label('可订阅')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sort')
                    ->label('排序')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('用户数')
                    ->counts('users')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('租户')
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'name'
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('状态'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('默认身份'),
                Tables\Filters\TernaryFilter::make('can_subscribe')
                    ->label('可订阅'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
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
