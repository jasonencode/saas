<?php

namespace App\Filament\Backend\Clusters\User\Resources\UserRelations\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class UserRelationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('user.name')
                    ->label('用户'),
                Infolists\Components\TextEntry::make('parent.name')
                    ->label('推荐用户'),
            ]);
    }
}
