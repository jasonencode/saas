<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\RelationManagers;

use App\Models\User\User;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
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
                    ->description(fn (User $user) => $user->name),
                Tables\Columns\IconColumn::make('pivot.is_used')
                    ->label('使用状态')
                    ->boolean(),
                Tables\Columns\TextColumn::make('pivot.expired_at')
                    ->label('过期时间')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('pivot.used_at')
                    ->label('使用时间')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('领取时间'),
            ])
            ->headerActions([
                Actions\Action::make('attach')
                    ->label('发放优惠券')
                    ->modalWidth(Width::Large)
                    ->schema([
                        Select::make('user_id')
                            ->label('选择用户')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => $this->searchUsers($search))
                            ->getOptionLabelUsing(fn ($value): string => User::select('username')->find($value)?->username ?? (string) $value)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $userId = $data['user_id'] ?? null;

                        if ($userId) {
                            $this->getOwnerRecord()->users()->syncWithoutDetaching([$userId]);

                            Notification::make()
                                ->title('发放成功')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Actions\DetachAction::make(),
            ])
            ->toolbarActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function searchUsers(string $search): array
    {
        return User::query()
            ->select(['id', 'username'])
            ->where('username', 'like', "%{$search}%")
            ->limit(50)
            ->pluck('username', 'id')
            ->all();
    }
}
