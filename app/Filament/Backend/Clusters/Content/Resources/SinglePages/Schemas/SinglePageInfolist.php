<?php

namespace App\Filament\Backend\Clusters\Content\Resources\SinglePages\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class SinglePageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 3,
                '2xl' => 4,
            ])
            ->components([
                Schemas\Components\Section::make('基本信息')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2,
                        'xl' => 2,
                        '2xl' => 3,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('标题'),
                        Infolists\Components\TextEntry::make('slug')
                            ->label('别名'),
                        Infolists\Components\TextEntry::make('content')
                            ->label('内容')
                            ->html(),
                    ]),
                Schemas\Components\Section::make('扩展内容')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 1,
                        'xl' => 1,
                        '2xl' => 1,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant')),
                        Infolists\Components\IconEntry::make('status')
                            ->label(__('backend.status')),
                        Infolists\Components\TextEntry::make('sort')
                            ->label(__('backend.sort')),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('backend.created_at'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('backend.updated_at'))
                            ->dateTime(),
                    ]),
            ]);
    }
}
