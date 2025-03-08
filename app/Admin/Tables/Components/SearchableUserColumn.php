<?php

namespace App\Admin\Tables\Components;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class SearchableUserColumn
{
    public static function make(string $label = '用户'): TextColumn
    {
        return TextColumn::make('user.info.nickname')
            ->label($label)
            ->description(fn($record) => $record->user?->username)
            ->searchable(true, function(Builder $query, string $search) {
                $query->whereHas('user', function(Builder $query) use ($search) {
                    $query->where('username', 'like', "%$search%")
                        ->orWhereHas('info', function(Builder $query) use ($search) {
                            $query->where('nickname', 'like', "%$search%");
                        });
                });
            });
    }
}
