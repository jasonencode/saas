<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\SinglePages\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class SinglePageForm
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
                        Forms\Components\TextInput::make('title')
                            ->label('标题')
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->label('别名')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\RichEditor::make('content')
                            ->label('内容')
                            ->resizableImages()
                            ->required()
                            ->grow(),
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
                        CustomUpload::cover(),
                        Forms\Components\TextInput::make('views')
                            ->label('浏览量')
                            ->integer()
                            ->default(0),
                        Forms\Components\TextInput::make('sort')
                            ->label(__('backend.sort'))
                            ->required()
                            ->helperText('数字越大越靠前')
                            ->integer()
                            ->default(0),
                        Forms\Components\Toggle::make('status')
                            ->label(__('backend.status')),
                    ]),
            ]);
    }
}
