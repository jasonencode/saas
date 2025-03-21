<?php

namespace App\Filament\Backend\Clusters\Settings\Resources;

use App\Enums\AdminType;
use App\Filament\Backend\Clusters\Settings;
use App\Filament\Backend\Clusters\Settings\Resources\StafferResource\Pages\ManageStaffers;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Administrator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StafferResource extends Resource
{
    protected static ?string $model = Administrator::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = Settings::class;

    protected static ?string $navigationGroup = '租户管理';

    protected static ?string $modelLabel = '租户用户';

    protected static ?string $navigationLabel = '租户用户';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('type')
                    ->default(AdminType::Tenant),
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
                            ->label('昵称'),
                        CustomUpload::make('avatar')
                            ->label('头像'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('type', AdminType::Tenant)->latest())
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像'),
                Tables\Columns\TextColumn::make('name')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('username')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('tenants.name')
                    ->label('团队')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('角色')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStaffers::route('/'),
        ];
    }
}
