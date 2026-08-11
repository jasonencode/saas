<?php

namespace App\Filament\Forms\Components;

use App\Models\User\User;
use Filament\Forms\Components\Select;

class UserSelect
{
    public static function make(string $name = 'user_id', string $label = '选择用户'): Select
    {
        return Select::make($name)
            ->label($label)
            ->searchable()
            ->preload()
            ->getSearchResultsUsing(function (string $search): array {
                return User::with('profile')
                    ->where('username', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($q) use ($search) {
                        $q->where('nickname', 'like', "%{$search}%");
                    })
                    ->limit(50)
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
}
