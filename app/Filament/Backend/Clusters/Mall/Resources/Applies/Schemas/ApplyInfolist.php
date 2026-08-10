<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Applies\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ApplyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('申请信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->icon(Heroicon::OutlinedBuildingOffice),
                        Infolists\Components\TextEntry::make('store_name')
                            ->label('店铺名称')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('store_description')
                            ->label('店铺描述')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('contactor')
                            ->label('联系人')
                            ->icon(Heroicon::OutlinedUser),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('联系电话')
                            ->icon(Heroicon::OutlinedPhone)
                            ->copyable(),
                    ]),
                Schemas\Components\Section::make('资质证件')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\ImageEntry::make('front')
                            ->label('身份证正面（国徽面）'),
                        Infolists\Components\ImageEntry::make('back')
                            ->label('身份证背面（人像面）'),
                        Infolists\Components\ImageEntry::make('license')
                            ->label('企业营业执照'),
                    ]),
                Schemas\Components\Section::make('审核信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('backend.status'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('审核人')
                            ->badge()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('reason')
                            ->label('拒绝理由')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('remark')
                            ->label('审核备注')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
