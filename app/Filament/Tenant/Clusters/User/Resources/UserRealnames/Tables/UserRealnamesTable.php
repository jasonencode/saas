<?php

namespace App\Filament\Tenant\Clusters\User\Resources\UserRealnames\Tables;

use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Filament\Actions\User\ApproveRealnameAction;
use App\Filament\Actions\User\RejectRealnameAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class UserRealnamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.username')
                    ->label('用户名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('昵称'),
                Tables\Columns\TextColumn::make('type')
                    ->label('认证类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->label('真实姓名'),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (RealnameStatus $state): string => $state->getColor()),
                Tables\Columns\TextColumn::make('reject_reason')
                    ->label('拒绝原因')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('verified_at')
                    ->label('认证时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('申请时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('认证类型')
                    ->options(RealnameType::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options(RealnameStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                ApproveRealnameAction::make(),
                RejectRealnameAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
