<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\StafferResource\Pages;

use App\Enums\AdminType;
use App\Filament\Backend\Clusters\Tenants\Resources\StafferResource;
use App\Filament\Forms\Components\CustomUpload;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ManageStaffers extends ManageRecords
{
    protected static string $resource = StafferResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('登录信息')
                    ->schema([
                        TextInput::make('username')
                            ->label('用户名')
                            ->readOnly(fn(string $operation): bool => $operation === 'edit')
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->required()
                            ->unique(ignoreRecord: true)
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
                Fieldset::make('用户信息')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(12)
                            ->label('昵称'),
                        CustomUpload::make('avatar')
                            ->label('头像'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('type', AdminType::Tenant)->latest())
            ->columns([
                ImageColumn::make('avatar')
                    ->label('头像'),
                TextColumn::make('name')
                    ->translateLabel(),
                TextColumn::make('username')
                    ->translateLabel(),
                TextColumn::make('teams.name')
                    ->label('团队')
                    ->badge()
                    ->color('danger'),
                TextColumn::make('roles.name')
                    ->label('角色')
                    ->badge(),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
