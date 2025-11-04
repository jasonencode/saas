<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Imports\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ImportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('文件名称'),
                Tables\Columns\TextColumn::make('file_path')
                    ->label('文件路径'),
                Tables\Columns\TextColumn::make('importer')
                    ->label('导表工具'),
                Tables\Columns\TextColumn::make('total_rows')
                    ->label('总行数'),
                Tables\Columns\TextColumn::make('processed_rows')
                    ->label('处理完成'),
                Tables\Columns\TextColumn::make('successful_rows')
                    ->label('成功行数'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('用户'),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('完成时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->recordActions([
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
