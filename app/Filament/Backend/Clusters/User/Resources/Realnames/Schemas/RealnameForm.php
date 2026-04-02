<?php

namespace App\Filament\Backend\Clusters\User\Resources\Realnames\Schemas;

use App\Enums\RealnameStatus;
use App\Enums\RealnameType;
use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class RealnameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns()
            ->components([
                Schemas\Components\Section::make('认证信息')
                    ->columns()
                    ->components([
                        Forms\Components\TextInput::make('user.username')
                            ->label('用户名')
                            ->disabled(),
                        Forms\Components\TextInput::make('name')
                            ->label('真实姓名/企业名称')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('认证类型')
                            ->options(RealnameType::class)
                            ->disabled()
                            ->afterStateUpdated(fn () => null),
                    ]),
                Schemas\Components\Section::make('个人认证资料')
                    ->columns()
                    ->visible(fn (array $values) => ($values['type'] ?? null) === RealnameType::Personal->value)
                    ->components([
                        Forms\Components\TextInput::make('id_card_number')
                            ->label('身份证号')
                            ->required(),
                        CustomUpload::make('id_card_frontl')
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
                Schemas\Components\Section::make('审核结果')
                    ->columns()
                    ->visible(fn (array $values) => ($values['status'] ?? null) === RealnameStatus::Rejected->value)
                    ->components([
                        Forms\Components\TextInput::make('reject_reason')
                            ->label('拒绝原因')
                            ->required(),
                    ]),
            ]);
    }
}
