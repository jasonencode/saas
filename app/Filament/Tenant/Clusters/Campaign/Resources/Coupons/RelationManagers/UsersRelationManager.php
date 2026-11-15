<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\RelationManagers;

use App\Models\User\User;
use App\Services\Campaign\CouponService;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

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
                    ->dateTime()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('pivot.used_at')
                    ->label('使用时间')
                    ->dateTime()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('领取时间')
                    ->dateTime(),
            ])
            // 已核销的持有记录不可选中：解除后售后退款无法回查（CAMP-09）
            ->checkIfRecordIsSelectableUsing(fn (User $record): bool => !$this->isUsed($record))
            ->headerActions([
                Actions\Action::make('attach')
                    ->label('发放优惠券')
                    ->modalWidth(Width::Large)
                    ->schema([
                        Select::make('user_id')
                            ->label('选择用户')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => $this->searchUsers($search))
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $userId = $data['user_id'] ?? null;

                        if (!$userId) {
                            return;
                        }

                        $coupon = $this->getOwnerRecord();

                        // 仅限本租户用户：防止跨租户发券（CAMP-09）
                        $user = User::query()
                            ->whereHas('tenants', fn (Builder $query) => $query->whereKey($coupon->tenant_id))
                            ->find($userId);

                        if (!$user) {
                            $this->failureNotificationTitle('发放失败');
                            $this->failureNotificationBody('用户不存在或不属于当前租户');
                            $this->failure();

                            return;
                        }

                        try {
                            service(CouponService::class)->sendToUser($coupon, $user);

                            $this->successNotificationTitle('发放成功');
                            $this->success();
                        } catch (InvalidArgumentException $exception) {
                            $this->failureNotificationTitle('发放失败');
                            $this->failureNotificationBody($exception->getMessage());
                            $this->failure();
                        }
                    }),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    // 已核销的券禁止解除持有（CAMP-09）
                    Actions\DetachAction::make()
                        ->hidden(fn (User $record): bool => $this->isUsed($record)),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DetachBulkAction::make(),
                ]),
            ]);
    }

    /**
     * 持有记录是否已核销
     */
    protected function isUsed(User $record): bool
    {
        return (bool) ($record->pivot?->is_used ?? false);
    }

    /**
     * 搜索当前租户下的用户
     *
     * 发券只能选本租户用户，防止把券发给其他租户的用户（CAMP-09）。
     *
     * @return array<int, string> 用户 ID => 用户名
     */
    protected function searchUsers(string $search): array
    {
        return User::query()
            ->select(['id', 'username'])
            ->whereHas('tenants', fn (Builder $query) => $query->whereKey($this->getOwnerRecord()->tenant_id))
            ->where('username', 'like', "%{$search}%")
            ->limit(50)
            ->pluck('username', 'id')
            ->all();
    }
}
