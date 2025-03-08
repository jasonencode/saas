<?php

namespace App\Admin\Clusters\Users\Resources\UserResource\Pages;

use App\Admin\Clusters\Users\Resources\UserResource;
use App\Admin\Forms\Components\CustomUpload;
use App\Enums\Gender;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('team_id')
                    ->label('团队')
                    ->required()
                    ->relationship(
                        name: 'team',
                        titleAttribute: 'name'
                    ),
                Fieldset::make('登录信息')
                    ->schema([
                        TextInput::make('username')
                            ->label('用户名')
                            ->readOnly(fn(string $operation): bool => $operation === 'edit')
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function(Unique $rule, Get $get) {
                                    return $rule->where('team_id', $get('team_id'));
                                }
                            )
                            ->minLength(4)
                            ->maxLength(32),
                        TextInput::make('password')
                            ->label('登录密码')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->rule(Password::min(6)),
                    ]),
                Fieldset::make('用户资料')
                    ->relationship('info')
                    ->schema([
                        TextInput::make('nickname')
                            ->required()
                            ->minLength(2)
                            ->maxLength(12)
                            ->label('昵称'),
                        DatePicker::make('birthday')
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->closeOnDateSelection(),
                        Radio::make('gender')
                            ->label('性别')
                            ->options(Gender::class)
                            ->default(Gender::Secret)
                            ->inline()
                            ->inlineLabel(false),
                        CustomUpload::make('avatar')
                            ->label('头像')
                            ->avatar()
                            ->imageEditor()
                            ->imageResizeTargetWidth(200)
                            ->imageResizeTargetHeight(200),
                        Textarea::make('description')
                            ->label('简介')
                            ->rows(3)
                            ->columnSpanFull()
                            ->maxLength(255),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('info.avatar')
                    ->label('头像')
                    ->circular(),
                TextColumn::make('id'),
                TextColumn::make('team.name')
                    ->label('团队'),
                TextColumn::make('username')
                    ->translateLabel(),
                TextColumn::make('info.nickname')
                    ->label('昵称'),
                TextColumn::make('info.gender')
                    ->label('性别')
                    ->badge(),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
