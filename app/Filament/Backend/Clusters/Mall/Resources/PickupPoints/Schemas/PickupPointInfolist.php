<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\PickupPoints\Schemas;

use Filament\Infolists;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class PickupPointInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('自提点信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('name')
                            ->label('自提点名称'),
                        Infolists\Components\TextEntry::make('contact')
                            ->label('联系人')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('联系电话')
                            ->copyable()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('full_address')
                            ->label('详细地址')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('backend.status'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('sort')
                            ->label(__('backend.sort')),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('backend.created_at')),
                        Infolists\Components\TextEntry::make('remark')
                            ->label('备注')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
