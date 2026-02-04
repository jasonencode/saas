<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Coupons\RelationManagers;

use App\Models\User;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = '持有用户';

    protected static ?string $modelLabel = '用户';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->searchable()
                    ->description(fn(User $user) => $user->name),
                Tables\Columns\IconColumn::make('pivot.is_used')
                    ->label('使用状态')
                    ->boolean(),
                Tables\Columns\TextColumn::make('pivot.expired_at')
                    ->label('过期时间'),
                Tables\Columns\TextColumn::make('pivot.used_at')
                    ->label('使用时间'),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('领取时间'),
            ])
            ->headerActions([
                Actions\AttachAction::make(),
            ])
            ->recordActions([
                Actions\DetachAction::make(),
            ]);
    }
}

