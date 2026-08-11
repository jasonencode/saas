<?php

namespace App\Filament\Forms\Components;

use App\Models\System\Tenant;
use App\Models\User\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

class UserSelect
{
    const int SEARCH_RESULT_LIMIT = 10;

    /**
     * 创建用户选择器
     *
     * @param  string  $name  字段名称
     *
     * @return Select 用户选择组件
     */
    public static function make(string $name = 'user_id'): Select
    {
        return Select::make($name)
            ->searchable()
            ->preload()
            ->getSearchResultsUsing(function (string $search): array {
                return User::with('profile')
                    ->where('username', 'like', "%$search%")
                    ->orWhereHas('profile', function ($q) use ($search) {
                        $q->where('nickname', 'like', "%$search%");
                    })
                    ->limit(self::SEARCH_RESULT_LIMIT)
                    ->get()
                    ->mapWithKeys(fn (User $user) => [
                        $user->id => $user->username.' ['.($user->profile?->nickname ?? '').']',
                    ])
                    ->toArray();
            })
            ->getOptionLabelUsing(function (mixed $value): string {
                $user = User::with('profile')->find($value);

                return $user ? $user->username.' ['.($user->profile?->nickname ?? '').']' : $value;
            });
    }

    /**
     * 按租户筛选用户选择器
     *
     * @param  Closure|int|Tenant|null  $tenant  租户（租户对象、租户 ID、闭包或 null）
     * @param  string  $name  字段名称
     *
     * @return Select 用户选择组件
     */
    public static function ofTenant(Closure|int|Tenant|null $tenant, string $name = 'user_id'): Select
    {
        return static::make($name)
            ->getSearchResultsUsing(function (string $search, Get $get) use ($tenant): array {
                $resolvedTenantId = static::resolveTenantId($tenant, $get);

                return User::with('profile')
                    ->whereHas('tenants', function (Builder $query) use ($resolvedTenantId) {
                        $query->where('tenants.id', $resolvedTenantId)
                            ->withoutGlobalScopes();
                    })
                    ->where(function (Builder $query) use ($search) {
                        $query->where('username', 'like', "%$search%")
                            ->orWhereHas('profile', fn (Builder $q) => $q->where('nickname', 'like', "%$search%"));
                    })
                    ->limit(self::SEARCH_RESULT_LIMIT)
                    ->get()
                    ->mapWithKeys(fn (User $user) => [
                        $user->id => $user->username.' ['.($user->profile?->nickname ?? '').']',
                    ])
                    ->toArray();
            });
    }

    /**
     * 解析租户ID
     *
     * @param  Closure|int|Tenant|null  $tenant  租户（租户对象、租户 ID、闭包或 null）
     * @param  Get  $get  表单状态获取器
     *
     * @return int|null 租户 ID
     */
    protected static function resolveTenantId(Closure|int|Tenant|null $tenant, Get $get): ?int
    {
        if ($tenant instanceof Closure) {
            $tenant = $tenant($get);
        }

        if ($tenant instanceof Tenant) {
            return $tenant->id;
        }

        return $tenant;
    }
}
