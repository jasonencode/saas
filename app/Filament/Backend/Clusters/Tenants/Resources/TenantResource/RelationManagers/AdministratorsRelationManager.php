<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\TenantResource\RelationManagers;

use App\Filament\Forms\Components\CustomUpload;
use App\Models\Administrator;
use App\Models\AdminRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\DetachBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
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
                ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular(),
                TextColumn::make('username')
                    ->label('用户名')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('昵称')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->label('角色'),
                IconColumn::make('status')
                    ->label('状态'),
                TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->headerActions([
                CreateAction::make(),
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->multiple()
                    ->recordSelectSearchColumns(['username', 'name'])
                    ->form(function(AttachAction $action) {
                        return [
                            $action->getRecordSelect(),
                            Select::make('role_id')
                                ->label('成员角色')
                                ->native(false)
                                ->multiple()
                                ->required()
                                ->options(AdminRole::whereBelongsTo($this->getOwnerRecord())->pluck('name', 'id')),
                        ];
                    })
                    ->action(function(array $data, AttachAction $action) {
                        $this->getOwnerRecord()->staffers()->attach($data['recordId']);
                        Administrator::find($data['recordId'])->each(function(Administrator $staffer) use ($data) {
                            $staffer->assignRole(Arr::map($data['role_id'], fn($id) => (int) $id));
                        });
                        $action->success();
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DetachAction::make()
                    ->action(function(Administrator $record, DetachAction $action) {
                        foreach ($record->roles()->pluck('id') as $roleId) {
                            $record->removeRole((int) $roleId);
                        }
                        $record->teams()->detach($this->getOwnerRecord());
                        $action->success();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->action(function(Collection $records, DetachBulkAction $action) {
                            foreach ($records as $record) {
                                foreach ($record->roles()->pluck('id') as $roleId) {
                                    $record->removeRole((int) $roleId);
                                }
                                $record->teams()->detach($this->getOwnerRecord());
                            }
                            $action->success();
                        }),
                ]),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')
                    ->label('用户名')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('成员姓名')
                    ->required(),
                TextInput::make('password')
                    ->label('登录密码')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                    ->dehydrated(fn(?string $state): bool => filled($state))
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->rule(Password::default()),
                Select::make('role')
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
