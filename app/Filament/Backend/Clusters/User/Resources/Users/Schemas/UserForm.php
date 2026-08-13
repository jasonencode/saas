<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\Schemas;

use App\Enums\User\Gender;
use App\Filament\Forms\Components\CustomUpload;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Section::make('登录信息')
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('用户名')
                            ->readOnly(fn (string $operation): bool => $operation === 'edit')
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->minLength(4)
                            ->maxLength(32)
                            ->suffixAction(Action::make('rand')
                                ->visible(fn (string $operation) => $operation === 'create')
                                ->icon('heroicon-o-sparkles')
                                ->action(function (Set $set): void {
                                    $prefix = '1'.random_int(3, 9);
                                    $suffix = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
                                    $set('username', $prefix.$suffix);
                                })),
                        Forms\Components\TextInput::make('password')
                            ->label('登录密码')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rule(Password::min(6)),
                    ]),
                Schemas\Components\Section::make('用户资料')
                    ->columns()
                    ->relationship('profile')
                    ->schema([
                        Forms\Components\TextInput::make('nickname')
                            ->required()
                            ->minLength(2)
                            ->maxLength(12)
                            ->label('昵称'),
                        Forms\Components\DatePicker::make('birthday')
                            ->label('生日')
                            ->displayFormat('Y-m-d')
                            ->closeOnDateSelection(),
                        Forms\Components\Radio::make('gender')
                            ->label('性别')
                            ->inline(false)
                            ->options(Gender::class)
                            ->default(Gender::Secret),
                        CustomUpload::make('avatar')
                            ->label('头像')
                            ->avatar()
                            ->imageEditor()
                            ->automaticallyResizeImagesToWidth(200)
                            ->automaticallyResizeImagesToHeight(200),
                        Forms\Components\Textarea::make('description')
                            ->label('简介')
                            ->rows(3)
                            ->columnSpanFull()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
