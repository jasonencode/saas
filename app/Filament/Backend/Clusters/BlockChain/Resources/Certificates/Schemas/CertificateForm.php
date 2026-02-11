<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Certificates\Schemas;

use App\Enums\CertificateSignType;
use App\Enums\CertificateType;
use Filament\Forms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\ToggleButtons::make('type')
                    ->label('证书类型')
                    ->inline()
                    ->options(CertificateType::class)
                    ->default(CertificateType::Certificate),
                Fieldset::make('证书信息')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('country_name')
                            ->label('(C)-国家名称')
                            ->required()
                            ->default('CN')
                            ->helperText('证书持有者所在的国家'),
                        Forms\Components\TextInput::make('state_or_province_name')
                            ->label('(ST)-州/省名称')
                            ->required()
                            ->helperText('证书持有者所在的州或省份'),
                        Forms\Components\TextInput::make('locality_name')
                            ->label('(L)-地区/城市名称')
                            ->required()
                            ->helperText('请证书持有者所在的城市或地区'),
                        Forms\Components\TextInput::make('organization_name')
                            ->label('(O)-组织名称')
                            ->required()
                            ->helperText('证书持有者的公司或组织名称'),
                        Forms\Components\TextInput::make('organizational_unit_name')
                            ->label('(OU)-组织单位名称')
                            ->required()
                            ->helperText('组织内的部门或单位'),
                        Forms\Components\TextInput::make('common_name')
                            ->label('(CN)-通用名称')
                            ->required()
                            ->helperText('网站域名，服务器主机名，个人姓名'),
                        Forms\Components\TextInput::make('email_address')
                            ->label('(E)-邮箱地址')
                            ->required()
                            ->helperText('请填写证书持有者的邮箱地址'),
                    ]),
                Fieldset::make('其他配置')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('sign_type')
                            ->label('密钥类型')
                            ->required()
                            ->native(false)
                            ->options(CertificateSignType::class)
                            ->default(CertificateSignType::RSA4096),
                        Forms\Components\TextInput::make('password')
                            ->label('私钥密码')
                            ->required()
                            ->password()
                            ->revealable()
                            ->rule(Password::default()),
                        Forms\Components\TextInput::make('days')
                            ->label('有效期')
                            ->required()
                            ->default(10950)
                            ->integer()
                            ->minValue(365)
                            ->helperText('默认时间30年')
                            ->suffix('天'),
                    ]),
            ]);
    }
}