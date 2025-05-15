<?php

namespace App\Filament\Backend\Clusters\Settings\Resources;

use App\Enums\AdminType;
use App\Filament\Actions\DisableBulkAction;
use App\Filament\Actions\EnableBulkAction;
use App\Filament\Backend\Clusters\Settings;
use App\Filament\Backend\Clusters\Settings\Resources\AdministratorResource\Pages;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Administrator;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdministratorResource extends Resource
{
    protected static ?string $model = Administrator::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = Settings::class;

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function(Builder $query): Builder {
                return $query->where('type', AdminType::Admin)->latest();
            })
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名'),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label('角色')
                    ->action(
                        Tables\Actions\Action::make('role')
                            ->label('编辑角色')
                            ->fillForm(fn(Administrator $record) => [
                                'roles' => $record->roles->pluck('id')->toArray(),
                            ])
                            ->form([
                                Select::make('roles')
                                    ->label('用户角色')
                                    ->relationship(
                                        name: 'roles',
                                        titleAttribute: 'name'
                                    )
                                    ->columnSpanFull()
                                    ->dehydrated(false)
                                    ->multiple()
                                    ->native(false)
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    EnableBulkAction::make(),
                    DisableBulkAction::make(),
                ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('登录信息')
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
                Forms\Components\Fieldset::make('用户信息')
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
                            ->imageResizeTargetWidth(200)
                            ->imageResizeTargetHeight(200),
                    ]),
                Forms\Components\Fieldset::make('角色')
                    ->schema([
                        Forms\Components\Select::make('role_id')
                            ->label('用户角色')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                            )
                            ->columnSpanFull()
                            ->dehydrated(false)
                            ->multiple()
                            ->native(false)
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAdministrators::route('/'),
        ];
    }
}
