<?php

namespace App\Admin\Clusters\Contents\Resources;

use App\Admin\Clusters\Contents;
use App\Admin\Clusters\Contents\Resources\SensitiveResource\Pages\ManageSensitives;
use App\Models\Sensitive;
use Filament\Resources\Resource;

class SensitiveResource extends Resource
{
    protected static ?string $model = Sensitive::class;

    protected static ?string $modelLabel = '敏感词';

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = '敏感词管理';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Contents::class;

    public static function getPages(): array
    {
        return [
            'index' => ManageSensitives::route('/'),
        ];
    }
}
