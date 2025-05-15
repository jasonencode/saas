<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\TenantResource\Pages;

use App\Filament\Backend\Clusters\Tenants\Resources\TenantResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Fieldset::make('基础信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('名称')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('slug')
                            ->label('简称')
                            ->copyable(),
                        Infolists\Components\ImageEntry::make('avatar')
                            ->label('LOGO')
                            ->circular(),
                        Infolists\Components\IconEntry::make('status')
                            ->label('状态'),
                    ]),
                Infolists\Components\Fieldset::make('时间信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('expired_at')
                            ->label('过期时间'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('创建时间'),
                    ]),
            ]);
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('返回列表')
                ->icon('heroicon-o-arrow-small-left')
                ->url(self::$resource::getUrl()),
        ];
    }
}
