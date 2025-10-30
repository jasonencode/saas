<?php

namespace App\Filament\Backend\Clusters\Users\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户')
                    ->badge(),
                Tables\Columns\TextColumn::make('id')
                    ->label('用户UID'),
                Tables\Columns\ImageColumn::make('info.avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('info.nickname')
                    ->label('昵称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('所属租户')
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'name'
                    ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
                Actions\Action::make('token')
                    ->fillForm(fn(User $user) => ['token' => $user->createToken('T:0')->plainTextToken])
                    ->schema([
                        TextInput::make('token'),
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
