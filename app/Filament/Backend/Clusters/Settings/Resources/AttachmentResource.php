<?php

namespace App\Filament\Backend\Clusters\Settings\Resources;

use App\Filament\Backend\Clusters\Settings;
use App\Filament\Backend\Clusters\Settings\Resources\AttachmentResource\Pages\ManageAttachments;
use App\Models\Attachment;
use Filament\Resources\Resource;

class AttachmentResource extends Resource
{
    protected static ?string $model = Attachment::class;

    protected static ?string $modelLabel = '附件';

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = '附件管理';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 101;

    public static function getPages(): array
    {
        return [
            'index' => ManageAttachments::route('/'),
        ];
    }
}
