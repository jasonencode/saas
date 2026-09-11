<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\RelationManagers;

use App\Models\User\Identity;
use Filament\Actions;
use Filament\Actions\AttachAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountsRelationManager extends RelationManager
{
    protected static string $relationship = 'discounts';

    protected static ?string $modelLabel = '身份折扣';

    protected static ?string $title = '身份折扣';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('身份名称'),
                Tables\Columns\TextColumn::make('percent')
                    ->label('折扣')
                    ->formatStateUsing(fn ($state): string => sprintf('%s 折（%d%%）', rtrim(rtrim(number_format((float) $state / 10, 1, '.', ''), '0'), '.'), $state)),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('创建时间')
                    ->dateTime(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectOptionsQuery(fn (RelationManager $livewire, $query) => $query
                        ->where('tenant_id', $livewire->getOwnerRecord()->tenant_id)
                        ->where('status', true))
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('身份')
                            ->required()
                            ->rule(function (RelationManager $livewire): \Closure {
                                // 跨租户错配校验：身份必须与商品同租户
                                return fn (string $attribute, $value, \Closure $fail) => Identity::query()
                                    ->whereKey($value)
                                    ->where('tenant_id', $livewire->getOwnerRecord()->tenant_id)
                                    ->exists() || $fail('身份与商品不属于同一租户');
                            }),
                        Forms\Components\TextInput::make('percent')
                            ->label('折扣百分比')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(99)
                            ->required()
                            ->helperText('80 表示打 8 折，范围 1-99'),
                    ]),
            ])
            ->recordActions([
                Actions\DetachAction::make(),
            ]);
    }
}
