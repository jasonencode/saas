<?php

namespace App\Filament\Backend\Clusters\Users\Resources\UserResource\Pages;

use App\Filament\Backend\Clusters\Users\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label('返回列表')
                ->icon('heroicon-o-arrow-small-left')
                ->url(self::$resource::getUrl()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(5)
            ->schema([
                ImageEntry::make('info.avatar')
                    ->label('头像')
                    ->circular(),
                TextEntry::make('tenant.name')
                    ->label('团队'),
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
