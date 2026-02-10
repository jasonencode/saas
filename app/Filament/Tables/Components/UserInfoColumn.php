<?php

namespace App\Filament\Tables\Components;

use Deldius\UserField\UserColumn;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Builder;

class UserInfoColumn
{
    public static function make(string $name = 'user'): UserColumn
    {
        return UserColumn::make($name)
            ->label('用户')
            ->size(Size::ExtraSmall)
            ->searchable(query: function (Builder $query, string $search) {
                return $query->whereHas('user', function (Builder $builder) use ($search) {
                    $builder->whereLike('username', "%$search%")
                        ->orWhereHas('info', function (Builder $builder) use ($search) {
                            $builder->whereLike('nickname', "%$search%");
                        });
                });
            });
    }
}