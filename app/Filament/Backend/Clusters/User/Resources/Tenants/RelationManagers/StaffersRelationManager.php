<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tenants\RelationManagers;

use App\Enums\AdminType;
use App\Filament\Actions\Tenant\StafferLoginAction;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Administrator;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

class StaffersRelationManager extends RelationManager
{
    protected static string $relationship = 'administrators';

    protected static ?string $modelLabel = '员工';

    protected static ?string $title = '租户成员';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Forms\Components\Hidden::make('type')
                    ->default(AdminType::Tenant),
                Schemas\Components\Fieldset::make('登录信息')
                    ->components([
                        Forms\Components\TextInput::make('username')
                            ->label('用户名')
                            ->readOnly(fn (string $operation): bool => $operation === 'edit')
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->minLength(4)
                            ->maxLength(32),
                        Forms\Components\TextInput::make('password')
                            ->label('登录密码')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rule(Password::min(6)),
                    ]),
                Schemas\Components\Fieldset::make('用户信息')
                    ->components([
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
                            ->automaticallyResizeImagesToWidth(200)
                            ->automaticallyResizeImagesToHeight(200),
                    ]),
                Schemas\Components\Fieldset::make('角色')
                    ->components([
                        Forms\Components\Select::make('roles')
                            ->label('用户角色')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo($this->getOwnerRecord())
                            )
                            ->columnSpanFull()
                            ->dehydrated(false)
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->latest('administrators.created_at');
            })
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('昵称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label('角色'),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
                Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['username', 'name'])
                    ->recordTitle(function (Administrator $record) {
                        return $record->name;
                    })
                    ->schema(fn (Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('roles')
                            ->label('分配角色')
                            ->required()
                            ->multiple()
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo($this->getOwnerRecord())
                            )
                            ->preload(),
                    ]),
            ])
            ->recordActions([
                StafferLoginAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\DetachAction::make(),
            ]);
    }
}
