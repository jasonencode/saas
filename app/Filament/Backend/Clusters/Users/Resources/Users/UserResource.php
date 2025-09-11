<?php

namespace App\Filament\Backend\Clusters\Users\Resources\Users;

use App\Enums\Gender;
use App\Filament\Backend\Clusters\Users\Resources\Users\Pages\ManageUsers;
use App\Filament\Backend\Clusters\Users\Resources\Users\Pages\ViewUser;
use App\Filament\Backend\Clusters\Users\Resources\Users\RelationManagers\RecordsRelationManager;
use App\Filament\Backend\Clusters\Users\UsersCluster;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UsersCluster::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户')
                    ->badge(),
                Tables\Columns\TextColumn::make('id')
                    ->label('用户UID'),
                Tables\Columns\ImageColumn::make('info.avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('info.nickname')
                    ->label('昵称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('所属租户')
                    ->native(false)
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'name'
                    ),
//                Tables\Filters\TrashedFilter::make()
//                    ->native(false),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
                Actions\Action::make('token')
                    ->fillForm(fn(User $user) => ['token' => $user->createToken('T:0')->plainTextToken])
                    ->schema([
                        Forms\Components\TextInput::make('token'),
                    ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
