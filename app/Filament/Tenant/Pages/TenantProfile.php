<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Tuupola\Base58;
use function Filament\authorize;

class TenantProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return '门店资料';
    }

    public static function canView(Model $tenant): bool
    {
        try {
            return authorize('edit', $tenant)->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Fieldset::make('基础信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('租户名称')
                            ->live(onBlur: true)
                            ->required(),
                        CustomUpload::make('avatar')
                            ->label('LOGO')
                            ->avatar()
                            ->imageEditor()
                            ->automaticallyResizeImagesToWidth(500)
                            ->automaticallyResizeImagesToHeight(500),
                    ]),
                Schemas\Components\Section::make('API 凭证')
                    ->collapsed()
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('app_key')
                            ->label('App Key')
                            ->unique()
                            ->copyable()
                            ->suffixAction(
                                Action::make('refresh')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(fn (Set $set) => $set('app_key', self::makeAppKey()))
                            ),
                        Forms\Components\TextInput::make('app_secret')
                            ->label('App Secret')
                            ->copyable()
                            ->suffixAction(
                                Action::make('refresh')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(fn (Set $set) => $set('app_secret', self::makeAppSecret()))
                            ),
                    ]),
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
