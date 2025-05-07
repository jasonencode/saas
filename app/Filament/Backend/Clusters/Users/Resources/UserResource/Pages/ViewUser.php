<?php

namespace App\Filament\Backend\Clusters\Users\Resources\UserResource\Pages;

use App\Filament\Backend\Clusters\Users\Resources\UserResource;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(5)
            ->schema([
                ImageEntry::make('info.avatar')
                    ->label('头像')
                    ->circular(),
                TextEntry::make('tenant.name')
                    ->label('租户'),
                TextEntry::make('username')
                    ->translateLabel()
                    ->copyable(),
                TextEntry::make('info.nickname')
                    ->label('昵称'),
                TextEntry::make('info.gender')
                    ->label('性别')
                    ->badge(),
            ]);
    }
}