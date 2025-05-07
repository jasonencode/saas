<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\StafferResource\Pages;

use App\Filament\Tenant\Clusters\Settings\Resources\StafferResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffer extends ViewRecord
{
    protected static string $resource = StafferResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('name')
                ->label('用户姓名'),
            TextEntry::make('username')
                ->label('登录名称'),
            ImageEntry::make('avatar')
                ->label('头像')
                ->circular(),
        ]);
    }

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label('返回列表')
                ->url(StafferResource::getUrl()),
        ];
    }
}
