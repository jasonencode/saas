<?php

namespace App\Filament\Tables\Columns;

use Closure;
use Deldius\UserField\UserColumn;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Builder;

class UserInfoColumn
{
    protected string $view = 'filament.tables.columns.user-info-column';

    /**
     * 用户信息列
     *
     * @param  string  $name  字段/关联名（支持 `pivot.user` 这类点路径）
     * @param  Closure|null  $searchQuery  自定义搜索条件；默认按 `$name` 关联搜索用户名/昵称
     */
    public static function make(string $name = 'user', ?Closure $searchQuery = null): UserColumn
    {
        return UserColumn::make($name)
            ->label('用户')
            ->size(Size::ExtraSmall)
            ->searchable(query: $searchQuery ?? static fn (Builder $query, string $search): Builder => $query->whereHas(
                $name,
                static fn (Builder $builder) => $builder
                    ->whereLike('username', "%{$search}%")
                    ->orWhereHas('profile', static fn (Builder $builder) => $builder->whereLike('nickname', "%{$search}%"))
            ));
    }

    /**
     * 记录本身即用户时的搜索条件
     *
     * @param  Builder  $query  查询
     * @param  string  $search  关键词
     *
     * @return Builder 查询
     */
    public static function selfSearch(Builder $query, string $search): Builder
    {
        return $query->where(static fn (Builder $builder) => $builder
            ->whereLike('username', "%{$search}%")
            ->orWhereHas('profile', static fn (Builder $builder) => $builder->whereLike('nickname', "%{$search}%")));
    }
}
