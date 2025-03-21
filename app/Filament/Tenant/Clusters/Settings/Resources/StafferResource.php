<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources;

use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Tenant\Clusters\Settings;
use App\Filament\Tenant\Clusters\Settings\Resources\StafferResource\Pages;
use App\Filament\Tenant\Clusters\Settings\Resources\StafferResource\RelationManagers;
use App\Models\Administrator;
use Filament\Facades\Filament;
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

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('username')
                    ->label('用户名')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')
                    ->label('成员姓名')
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->label('登录密码')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                    ->dehydrated(fn(?string $state): bool => filled($state))
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->rule(Password::default()),
                Forms\Components\Select::make('role')
                    ->label('成员角色')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant())
                    )
                    ->dehydrated(false)
                    ->native(false)
                    ->required()
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->selectablePlaceholder(false),
                CustomUpload::make('avatar')
                    ->label('头像')
                    ->avatar()
                    ->imageEditor()
                    ->imageResizeTargetWidth(200)
                    ->imageResizeTargetHeight(200),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('username')
                    ->label('成员名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('昵称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label('角色'),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ManageStaffers::route('/'),
            'view' => Pages\ViewStaffer::route('/{record}'),
        ];
    }
}
