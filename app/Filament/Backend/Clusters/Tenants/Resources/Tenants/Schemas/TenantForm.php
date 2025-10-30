<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\Tenants\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Overtrue\Pinyin\Pinyin;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('租户名称')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function(Set $set, ?string $state) {
                                if (!blank($state)) {
                                    $set('slug', Pinyin::abbr($state)->join(''));
                                }
                            })
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->label('租户简称')
                            ->helperText('涉及到登录地址，域名等信息，全局需唯一')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('expired_at')
                            ->label('过期时间')
                            ->displayFormat('Y-m-d')
                            ->default(now()->addYear())
                            ->required(),
                        CustomUpload::make('avatar')
                            ->label('租户LOGO')
                            ->avatar()
                            ->imageEditor()
                            ->imageResizeTargetWidth(200)
                            ->imageResizeTargetHeight(200),
                    ]),
                Schemas\Components\Fieldset::make('扩展配置')
                    ->columns()
                    ->schema([
                    ]),
                Forms\Components\Toggle::make('status')
                    ->label('状态')
                    ->required(),
            ]);
    }
}
