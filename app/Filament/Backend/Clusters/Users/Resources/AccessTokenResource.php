<?php

namespace App\Filament\Backend\Clusters\Users\Resources;

use App\Filament\Backend\Clusters\Users;
use App\Filament\Backend\Clusters\Users\Resources\AccessTokenResource\Pages\ManageAccessTokens;
use Filament\Resources\Resource;
use Laravel\Sanctum\PersonalAccessToken;

class AccessTokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static ?string $modelLabel = '用户凭证';

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-bangladeshi';

    protected static ?string $navigationLabel = '凭证管理';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Users::class;

    public static function getPages(): array
    {
        return [
            'index' => ManageAccessTokens::route('/'),
        ];
    }
}
