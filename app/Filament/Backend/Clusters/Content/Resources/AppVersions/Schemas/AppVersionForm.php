<?php

namespace App\Filament\Backend\Clusters\Content\Resources\AppVersions\Schemas;

use App\Enums\PlatformType;
use Filament\Forms;
use Filament\Schemas\Schema;

class AppVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Radio::make('platform')
                    ->label('平台')
                    ->options(PlatformType::class)
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\TextInput::make('application_id')
                    ->label('包名')
                    ->required(),
                Forms\Components\TextInput::make('version')
                    ->label('版本号')
                    ->required()
                    ->rule('regex:/^\\d+(?:\\.\\d+){2}(?:-[0-9A-Za-z.-]+)?(?:\\+[0-9A-Za-z.-]+)?$/')
                    ->helperText('语义化版本号，如 1.0.0 或 1.2.3-beta'),
                Forms\Components\Repeater::make('description')
                    ->label('升级说明')
                    ->table([
                        Forms\Components\Repeater\TableColumn::make('说明项'),
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('item')
                            ->label('说明项')
                            ->placeholder('请输入说明项')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->defaultItems(1)
                    ->addActionLabel('增加说明项')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('download_url')
                    ->label('下载地址')
                    ->url()
                    ->required(),
                Forms\Components\DateTimePicker::make('publish_at')
                    ->label('发布时间')
                    ->helperText('设置发布时间，将在设置时间后，提示更新'),
                Forms\Components\Toggle::make('force')
                    ->label('强制更新'),
            ]);
    }
}
