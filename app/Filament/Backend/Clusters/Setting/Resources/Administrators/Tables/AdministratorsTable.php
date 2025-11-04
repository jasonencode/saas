<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Administrators\Tables;

use App\Enums\AdminType;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdministratorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label('角色'),
//                    ->action(
//                        Tables\Actions\Action::make('role')
//                            ->label('编辑角色')
//                            ->fillForm(fn(Administrator $record) => [
//                                'roles' => $record->roles->pluck('id')->toArray(),
//                            ])
//                            ->form([
//                                Select::make('roles')
//                                    ->label('用户角色')
//                                    ->relationship(
//                                        name: 'roles',
//                                        titleAttribute: 'name'
//                                    )
//                                    ->columnSpanFull()
//                                    ->dehydrated(false)
//                                    ->multiple()
//                                    ->searchable()
//                                    ->preload(),
//                            ]),
//                    ),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
