<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\Tables;

use App\Filament\Actions\User\AuthorizeTenantAction;
use App\Filament\Actions\User\GenerateTokenAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('用户UID')
                    ->sortable(),
                Tables\Columns\ImageColumn::make('profile.avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('profile.nickname')
                    ->label('昵称')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('tenants.name')
                    ->label('所属租户')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('identities.name')
                    ->label('身份')
                    ->badge()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label(__('backend.tenant'))
                    ->relationship(
                        name: 'tenants',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->ofEnabled()
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    AuthorizeTenantAction::make(),
                    GenerateTokenAction::make(),
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                    Actions\RestoreAction::make(),
                    Actions\ForceDeleteAction::make(),
                ]),
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
