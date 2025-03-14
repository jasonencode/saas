<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\TenantResource\Pages;

use App\Filament\Backend\Clusters\Tenants\Resources\TenantResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Fieldset::make('基础信息')
                    ->schema([
                        TextEntry::make('name')
                            ->label('名称')
                            ->copyable(),
                        TextEntry::make('slug')
                            ->label('简称')
                            ->copyable(),
                        ImageEntry::make('avatar')
                            ->label('LOGO')
                            ->circular(),
                        IconEntry::make('status')
                            ->label('状态'),
                    ]),
                Fieldset::make('时间信息')
                    ->schema([
                        TextEntry::make('expired_at')
                            ->label('过期时间'),
                        TextEntry::make('created_at')
                            ->label('创建时间'),
                    ]),
            ]);
    }

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label('返回列表')
                ->url(TenantResource::getUrl()),
        ];
    }
}
