<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Administrators\Schemas;

use App\Enums\AdminType;
use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdministratorForm
{
    public static function configure(Schema $schema, AdminType $defaultType): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Forms\Components\Hidden::make('type')
                    ->default($defaultType),
                Schemas\Components\Fieldset::make('登录信息')
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
                Schemas\Components\Fieldset::make('用户信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(12)
                            ->label('姓名'),
                        CustomUpload::make('avatar')
                            ->label('头像')
                            ->avatar()
                            ->imageEditor()
                            ->imageEditorMode(2)
                            ->imageResizeTargetWidth(200)
                            ->imageResizeTargetHeight(200),
                    ]),
                Schemas\Components\Fieldset::make('角色')
                    ->schema([
                        Forms\Components\Select::make('role_id')
                            ->label('用户角色')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: function(Builder $query) {
                                    $query->whereDoesntHave('tenant');
                                }
                            )
                            ->columnSpanFull()
                            ->dehydrated(false)
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }
}
