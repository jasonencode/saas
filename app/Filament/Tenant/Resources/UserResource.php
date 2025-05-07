<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Wechat;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\ImageColumn::make('info.avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('wechat.name')
                    ->label('微信平台'),
                Tables\Columns\TextColumn::make('info.nickname')
                    ->label('微信昵称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wechat_id')
                    ->label('微信平台')
                    ->native(false)
                    ->options(Wechat::ofEnabled()->whereBelongsTo(Filament::getTenant())->pluck('name', 'id')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
