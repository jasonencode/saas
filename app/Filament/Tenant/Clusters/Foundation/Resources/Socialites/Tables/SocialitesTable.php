<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Socialites\Tables;

use App\Enums\Foundation\SocialiteProvider;
use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Actions;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SocialitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('account.provider')
                    ->label('第三方平台')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('平台名称')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('provider_id')
                    ->label('身份标识'),
                Tables\Columns\TextColumn::make('union_id')
                    ->label('UnionId'),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('过期时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->label('第三方平台')
                    ->options(SocialiteProvider::class)
                    ->query(function (Builder $query, $state): Builder {
                        return $query->whereHas('account', fn (Builder $q) => $q->where('provider', $state));
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('expired_at')
                    ->label('令牌状态')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['state'] ?? null) {
                            'valid' => $query->whereNull('expired_at')->orWhere('expired_at', '>', now()),
                            'expired' => $query->where('expired_at', '<=', now()),
                            default => $query,
                        };
                    })
                    ->schema([
                        Forms\Components\Select::make('state')
                            ->label('令牌状态')
                            ->options([
                                'valid' => '有效',
                                'expired' => '已过期',
                            ])
                            ->placeholder('全部'),
                    ]),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
