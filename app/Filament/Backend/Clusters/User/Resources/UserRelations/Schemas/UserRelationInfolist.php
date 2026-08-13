<?php

namespace App\Filament\Backend\Clusters\User\Resources\UserRelations\Schemas;

use Deldius\UserField\UserEntry;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class UserRelationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基础信息')
                    ->columns(3)
                    ->schema([
                        UserEntry::make('user')
                            ->label('用户'),
                        UserEntry::make('parent')
                            ->label('推荐用户')
                            ->placeholder('无推荐人'),
                        Infolists\Components\TextEntry::make('layer')
                            ->label('当前层级'),
                    ]),
                Schemas\Components\Fieldset::make('推荐路径')
                    ->columns(1)
                    ->schema([
                        Infolists\Components\TextEntry::make('path')
                            ->label('推荐路径')
                            ->placeholder('-'),
                    ]),
                Schemas\Components\Fieldset::make('团队统计')
                    ->schema([
                        Infolists\Components\TextEntry::make('direct_count')
                            ->label('直推用户'),
                        Infolists\Components\TextEntry::make('team_count')
                            ->label('团队用户'),
                    ]),
            ]);
    }
}
