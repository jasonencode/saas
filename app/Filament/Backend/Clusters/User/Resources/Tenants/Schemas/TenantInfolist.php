<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tenants\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基础信息')
                    ->components([
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
                            ->label(__('backend.status')),
                    ]),
                Schemas\Components\Fieldset::make('时间信息')
                    ->components([
                        Infolists\Components\TextEntry::make('expired_at')
                            ->label('过期时间'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('backend.created_at')),
                    ]),
            ]);
    }
}
