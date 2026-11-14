<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tenants\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use App\Support\Tenant\AppCredentialGenerator;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Overtrue\Pinyin\Pinyin;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Section::make('基本信息')
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('backend.tenant'))
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
                    ->schema([
                        Forms\Components\TextInput::make('app_key')
                            ->label('App Key')
                            ->default(fn () => AppCredentialGenerator::appKey())
                            ->unique()
                            ->suffixAction(
                                Action::make('refresh')
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->action(fn (Set $set) => $set('app_key', AppCredentialGenerator::appKey()))
                            ),
                        Forms\Components\TextInput::make('app_secret')
                            ->label('App Secret')
                            ->password()
                            ->revealable()
                            ->default(fn () => AppCredentialGenerator::appSecret())
                            ->suffixAction(
                                Action::make('refresh')
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->action(fn (Set $set) => $set('app_secret', AppCredentialGenerator::appSecret()))
                            ),
                    ]),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
            ]);
    }
}
