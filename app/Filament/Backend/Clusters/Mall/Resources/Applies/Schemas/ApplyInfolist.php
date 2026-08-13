<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Applies\Schemas;

use App\Models\Mall\StoreApply;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Support\Facades\Storage;

class ApplyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('申请信息')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('store_name')
                            ->label('店铺名称'),
                        Infolists\Components\TextEntry::make('contactor')
                            ->label('联系人'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('联系电话')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('store_description')
                            ->label('店铺描述')
                            ->columnSpanFull(),
                    ]),
                Schemas\Components\Fieldset::make('资质证件')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\ImageEntry::make('front')
                            ->label('身份证正面（国徽面）')
                            ->action(
                                MediaAction::make('front')
                                    ->label('身份证正面')
                                    ->modalWidth(Width::Large)
                                    ->visible(fn (StoreApply $record) => $record->front)
                                    ->media(fn (StoreApply $record) => Storage::url($record->front))
                            ),
                        Infolists\Components\ImageEntry::make('back')
                            ->label('身份证背面（人像面）')
                            ->action(
                                MediaAction::make('back')
                                    ->label('身份证背面')
                                    ->modalWidth(Width::Large)
                                    ->visible(fn (StoreApply $record) => $record->back)
                                    ->media(fn (StoreApply $record) => Storage::url($record->back))
                            ),
                        Infolists\Components\ImageEntry::make('license')
                            ->label('企业营业执照')
                            ->action(
                                MediaAction::make('license')
                                    ->label('企业营业执照')
                                    ->modalWidth(Width::Large)
                                    ->visible(fn (StoreApply $record) => $record->license)
                                    ->media(fn (StoreApply $record) => Storage::url($record->license))
                            ),
                    ]),
                Schemas\Components\Fieldset::make('审核信息')
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
