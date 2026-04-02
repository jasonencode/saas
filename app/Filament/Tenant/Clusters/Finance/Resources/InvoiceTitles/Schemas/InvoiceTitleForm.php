<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\InvoiceTitles\Schemas;

use App\Enums\InvoiceTitleType;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class InvoiceTitleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Section::make('基础信息')
                    ->columns()
                    ->components([
                        Forms\Components\Select::make('user_id')
                            ->label('用户')
                            ->options(fn () => User::pluck('username', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Radio::make('type')
                            ->label('类型')
                            ->options(InvoiceTitleType::class)
                            ->default(InvoiceTitleType::Personal)
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('title')
                            ->label('抬头名称')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('tax_no')
                            ->label('纳税人识别号')
                            ->helperText('企业需提供组织机构代码编号，个人提供身份证号')
                            ->required()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->maxLength(128),
                        Forms\Components\Toggle::make('is_default')
                            ->label('设为默认')
                            ->default(false),
                    ]),
                Schemas\Components\Section::make('企业信息')
                    ->columns()
                    ->visible(fn (Schemas\Components\Utilities\Get $get) => $get('type') === InvoiceTitleType::Enterprise)
                    ->components([
                        Forms\Components\TextInput::make('company_address')
                            ->label('企业地址')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('company_phone')
                            ->label('企业电话')
                            ->maxLength(32),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('开户行')
                            ->maxLength(64),
                        Forms\Components\TextInput::make('bank_account')
                            ->label('银行账号')
                            ->maxLength(64)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
