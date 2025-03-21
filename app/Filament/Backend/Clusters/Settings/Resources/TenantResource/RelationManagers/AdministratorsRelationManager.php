<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\TenantResource\RelationManagers;

use App\Enums\AdminType;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Administrator;
use App\Models\AdminRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdministratorsRelationManager extends RelationManager
{
    protected static string $relationship = 'administrators';

    protected static ?string $modelLabel = '成员';

    protected static ?string $title = '团队成员';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function(Builder $query): Builder {
                return $query->latest('administrators.created_at');
            })
            ->recordTitleAttribute('name')
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
                    ->label('状态'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->multiple()
                    ->recordSelectSearchColumns(['username', 'name'])
                    ->recordSelectOptionsQuery(fn(Builder $query) => $query->where('type', AdminType::Tenant))
                    ->form(function(Tables\Actions\AttachAction $action) {
                        return [
                            $action->getRecordSelect(),
                            Forms\Components\Select::make('role_id')
                                ->label('成员角色')
                                ->native(false)
                                ->multiple()
                                ->required()
                                ->options(AdminRole::whereBelongsTo($this->getOwnerRecord())->pluck('name', 'id')),
                        ];
                    })
                    ->action(function(array $data, Tables\Actions\AttachAction $action) {
                        $this->getOwnerRecord()->administrators()->attach($data['recordId']);
                        Administrator::find($data['recordId'])->each(function(Administrator $staffer) use ($data) {
                            $staffer->roles()->attach(Arr::map($data['role_id'], fn($id) => (int) $id));
                        });
                        $action->successNotificationTitle('关联成功');
                        $action->success();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make()
                    ->action(function(Administrator $record, Tables\Actions\DetachAction $action) {
                        foreach ($record->roles->pluck('id') as $roleId) {
                            $record->roles()->detach((int) $roleId);
                        }
                        $record->tenants()->detach($this->getOwnerRecord());
                        $action->successNotificationTitle('分离成功');
                        $action->success();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->action(function(Collection $records, Tables\Actions\DetachBulkAction $action) {
                            foreach ($records as $record) {
                                foreach ($record->roles->pluck('id') as $roleId) {
                                    $record->roles()->detach((int) $roleId);
                                }
                                $record->tenants()->detach($this->getOwnerRecord());
                            }
                            $action->successNotificationTitle('分离成功');
                            $action->success();
                        }),
                ]),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('type')
                    ->default(AdminType::Tenant),
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
                        modifyQueryUsing: fn(Builder $query) => $query->whereBelongsTo($this->getOwnerRecord())
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
}
