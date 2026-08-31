<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Topics\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class TopicInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns(4)
                    ->schema([
                        Schemas\Components\Grid::make(4)
                            ->columnSpan(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('tenant.name')
                                    ->label(__('backend.tenant'))
                                    ->badge(),
                                Infolists\Components\TextEntry::make('name')
                                    ->label('专题名称'),
                                Infolists\Components\TextEntry::make('slug')
                                    ->label('专题标识'),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('专题简介')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label(__('backend.status'))
                                    ->formatStateUsing(fn ($state) => $state ? '启用' : '禁用')
                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('sort')
                                    ->label(__('backend.sort')),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('backend.created_at')),
                            ]),
                        Infolists\Components\ImageEntry::make('cover')
                            ->label('封面图')
                            ->disk('public'),
                    ]),
            ]);
    }
}
