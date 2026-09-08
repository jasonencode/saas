<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Suggests\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class SuggestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Fieldset::make('反馈信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('用户'),
                        Infolists\Components\TextEntry::make('type')
                            ->label('类型')
                            ->badge(),
                        Infolists\Components\TextEntry::make('contact')
                            ->label('联系方式')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->badge(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('提交时间'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('最后更新'),
                    ]),
            ]);
    }
}
