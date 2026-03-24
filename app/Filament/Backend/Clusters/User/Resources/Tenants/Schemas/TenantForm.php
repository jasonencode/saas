<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tenants\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Overtrue\Pinyin\Pinyin;
use Tuupola\Base58;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns()
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->label('租户名称')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (blank($state)) {
                                    return;
                                }

                                if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $state)) {
                                    $set('slug', Pinyin::abbr($state)->join(''));
                                } else {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->label('标识')
                            ->helperText('涉及到登录地址，域名等信息，全局需唯一')
                            ->required()
                            ->maxLength(255)
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
                            ->automaticallyResizeImagesToWidth(200)
                            ->automaticallyResizeImagesToHeight(200),
                    ]),
                Schemas\Components\Section::make('API 凭证')
                    ->collapsed()
                    ->columns()
                    ->components([
                        Forms\Components\TextInput::make('app_key')
                            ->label('App Key')
                            ->default(fn () => self::makeAppKey())
                            ->unique()
                            ->suffixAction(
                                Action::make('refresh')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(fn (Set $set) => $set('app_key', self::makeAppKey()))
                            ),
                        Forms\Components\TextInput::make('app_secret')
                            ->label('App Secret')
                            ->default(fn () => self::makeAppSecret())
                            ->suffixAction(
                                Action::make('refresh')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(fn (Set $set) => $set('app_secret', self::makeAppSecret()))
                            ),
                    ]),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
            ]);
    }

    protected static function makeAppKey(): string
    {
        return new Base58([
            'characters' => Base58::BITCOIN,
        ])->encode(hex2bin('00').random_bytes(11));
    }

    protected static function makeAppSecret(): string
    {
        return md5(uniqid(random_bytes(16), true));
    }
}
