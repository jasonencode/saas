<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\StafferResource\Pages;

use App\Filament\Tenant\Clusters\Settings\Resources\StafferResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffer extends ViewRecord
{
    protected static string $resource = StafferResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('name')
                ->label('用户姓名'),
            Infolists\Components\TextEntry::make('username')
                ->label('登录名称'),
            Infolists\Components\ImageEntry::make('avatar')
                ->label('头像')
                ->circular(),
        ]);
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('返回列表')
                ->url(StafferResource::getUrl()),
        ];
    }
}
