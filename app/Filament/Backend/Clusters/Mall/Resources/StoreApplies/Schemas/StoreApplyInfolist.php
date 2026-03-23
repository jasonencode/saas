<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\StoreApplies\Schemas;

use App\Filament\Infolists\Components\TextareaEntry;
use Filament\Infolists;
use Filament\Schemas\Schema;

class StoreApplyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Infolists\Components\TextEntry::make('tenant.name')
                    ->label(__('backend.tenant')),
                Infolists\Components\TextEntry::make('store_name')
                    ->label('店铺名称'),
                Infolists\Components\TextEntry::make('store_description')
                    ->label('店铺描述'),
                Infolists\Components\TextEntry::make('contactor')
                    ->label('联系人'),
                Infolists\Components\TextEntry::make('phone')
                    ->label('联系电话'),
                Infolists\Components\ImageEntry::make('front')
                    ->label('身份证正面（国徽面）'),
                Infolists\Components\ImageEntry::make('back')
                    ->label('身份证背面（人像面）'),
                Infolists\Components\ImageEntry::make('license')
                    ->label('企业营业执照'),
                Infolists\Components\TextEntry::make('status')
                    ->label('状态')
                    ->badge(),
                Infolists\Components\TextEntry::make('approver.name')
                    ->label('审核人')
                    ->badge(),
                Infolists\Components\TextEntry::make('reason')
                    ->label('拒绝理由'),
                Infolists\Components\TextEntry::make('remark')
                    ->label('审核备注'),
                TextareaEntry::make('ext')
                    ->label('扩展信息'),
            ]);
    }
}
