<?php

namespace App\Filament\RelationManagers\User;

use App\Enums\User\IdentityChannel;
use App\Filament\Tables\Columns\JsonColumn;
use App\Models\System\Tenant;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserIdentityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'identityLogs';

    protected static ?string $modelLabel = '身份变更记录';

    protected static ?string $title = '身份变更记录';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->when(
                filament()->getTenant(),
                fn (Builder $query, Tenant $tenant) => $query->whereBelongsTo($tenant),
            ))
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge()
                    ->visible(fn (): bool => filament()->getTenant() === null),
                Tables\Columns\TextColumn::make('beforeIdentity.name')
                    ->label('变更前身份')
                    ->badge()
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('id')
                    ->label('')
                    ->icon(Heroicon::OutlinedArrowRight)
                    ->size(IconSize::Small)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('afterIdentity.name')
                    ->label('变更后身份')
                    ->badge()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('channel')
                    ->label('变更渠道')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('变更时间')
                    ->dateTime()
                    ->sortable(),
                JsonColumn::make('source'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label('变更渠道')
                    ->options(IdentityChannel::class),
            ]);
    }
}
