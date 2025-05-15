<?php

namespace App\Filament\Backend\Clusters\Settings\Resources;

use App\Filament\Backend\Clusters\Settings;
use App\Filament\Backend\Clusters\Settings\Resources\BlackListResource\Pages;
use App\Models\BlackList;
use Filament\Resources\Resource;

class BlackListResource extends Resource
{
    protected static ?string $model = BlackList::class;

    protected static ?string $modelLabel = 'IP黑名单';

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = '黑名单管理';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 91;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBlackList::route('/'),
        ];
    }
}
