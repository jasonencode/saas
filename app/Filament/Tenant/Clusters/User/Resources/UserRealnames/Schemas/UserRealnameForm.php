<?php

namespace App\Filament\Tenant\Clusters\User\Resources\UserRealnames\Schemas;

use App\Enums\User\RealnameType;
use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class UserRealnameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Schemas\Components\Section::make('认证信息')
                    ->columns()
                    ->components([
                        Forms\Components\Select::make('type')
                            ->label('认证类型')
                            ->options(RealnameType::class)
                            ->required()
                            ->live(),
                    ]),
                Schemas\Components\Section::make('个人认证资料')
                    ->columns()
                    ->visible(fn (array $values) => ($values['type'] ?? null) === RealnameType::Personal->value)
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->label('真实姓名')
                            ->required(),
                        Forms\Components\TextInput::make('id_card_number')
                            ->label('身份证号')
                            ->required()
                            ->maxLength(18),
                        CustomUpload::make('id_card_front')
                            ->label('身份证正面')
                            ->image()
                            ->required(),
                        CustomUpload::make('id_card_back')
                            ->label('身份证背面')
                            ->image()
                            ->required(),
                    ]),
                Schemas\Components\Section::make('企业认证资料')
                    ->columns()
                    ->visible(fn (array $values) => ($values['type'] ?? null) === RealnameType::Enterprise->value)
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->label('企业名称')
                            ->required(),
                        Forms\Components\TextInput::make('contact_person')
                            ->label('联系人')
                            ->required(),
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('联系电话')
                            ->tel()
                            ->required(),
                        CustomUpload::make('business_license')
                            ->label('营业执照')
                            ->image()
                            ->required(),
                    ]),
            ]);
    }
}
