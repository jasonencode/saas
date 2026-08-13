<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Users\Schemas;

use App\Models\User\User;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Support\Facades\Storage;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(6)
            ->components([
                Infolists\Components\ImageEntry::make('profile.avatar')
                    ->label('头像')
                    ->circular()
                    ->action(
                        MediaAction::make('profile_avatar')
                            ->label('头像')
                            ->modalWidth(Width::Large)
                            ->visible(fn (User $record) => $record->profile?->avatar)
                            ->media(fn (User $record) => Storage::url($record->profile?->avatar))
                    ),
                Infolists\Components\TextEntry::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                Infolists\Components\TextEntry::make('username')
                    ->label('用户名')
                    ->copyable(),
                Infolists\Components\TextEntry::make('profile.nickname')
                    ->label('昵称')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('profile.birthday')
                    ->label('昵称')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('profile.gender')
                    ->label('性别')
                    ->badge(),
            ]);
    }
}
