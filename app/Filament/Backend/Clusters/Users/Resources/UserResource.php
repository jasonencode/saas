<?php

namespace App\Filament\Backend\Clusters\Users\Resources;

use App\Enums\Gender;
use App\Filament\Backend\Clusters\Users;
use App\Filament\Backend\Clusters\Users\Resources\UserResource\Pages;
use App\Filament\Backend\Clusters\Users\Resources\UserResource\RelationManagers;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Users::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tenant_id')
                    ->label('团队')
                    ->required()
                    ->native(false)
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'name'
                    ),
                Forms\Components\Fieldset::make('登录信息')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('用户名')
                            ->readOnly(fn(string $operation): bool => $operation === 'edit')
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function(Unique $rule, Forms\Get $get) {
                                    return $rule->where('tenant_id', $get('tenant_id'));
                                }
                            )
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
                Fieldset::make('用户资料')
                    ->relationship('info')
                    ->schema([
                        Forms\Components\TextInput::make('nickname')
                            ->required()
                            ->minLength(2)
                            ->maxLength(12)
                            ->label('昵称'),
                        Forms\Components\DatePicker::make('birthday')
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->closeOnDateSelection(),
                        Forms\Components\Radio::make('gender')
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
                        Forms\Components\Textarea::make('description')
                            ->label('简介')
                            ->rows(3)
                            ->columnSpanFull()
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('用户UID'),
                Tables\Columns\ImageColumn::make('info.avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('团队'),
                Tables\Columns\TextColumn::make('username')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('info.nickname')
                    ->label('昵称'),
                Tables\Columns\TextColumn::make('info.gender')
                    ->label('性别')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
