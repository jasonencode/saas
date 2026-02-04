<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\Schemas;

use App\Enums\Gender;
use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Fieldset::make('登录信息')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('用户名')
                            ->readOnly(fn(string $operation): bool => $operation === 'edit')
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->minLength(4)
                            ->maxLength(32),
                        Forms\Components\TextInput::make('password')
                            ->label('登录密码')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->rule(Password::min(6)),
                    ]),
                Schemas\Components\Fieldset::make('用户资料')
                    ->columnSpanFull()
                    ->relationship('info')
                    ->schema([
                        Forms\Components\TextInput::make('nickname')
                            ->required()
                            ->minLength(2)
                            ->maxLength(12)
                            ->label('昵称'),
                        Forms\Components\DatePicker::make('birthday')
                            ->displayFormat('Y-m-d')
                            ->closeOnDateSelection(),
                        Forms\Components\Radio::make('gender')
                            ->label('性别')
                            ->options(Gender::class)
                            ->default(Gender::Secret),
                        CustomUpload::make('avatar')
                            ->label('头像')
                            ->avatar()
                            ->imageEditor()
                            ->imageResizeTargetWidth(200)
                            ->imageResizeTargetHeight(200),
                        Forms\Components\Textarea::make('description')
                            ->label('简介')
                            ->rows(3)
                            ->columnSpanFull()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
