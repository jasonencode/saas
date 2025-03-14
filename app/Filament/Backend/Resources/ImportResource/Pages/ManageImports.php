<?php

namespace App\Filament\Backend\Resources\ImportResource\Pages;

use App\Filament\Backend\Resources\ImportResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManageImports extends ManageRecords
{
    protected static string $resource = ImportResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label('文件名称'),
                TextColumn::make('file_path')
                    ->label('文件路径'),
                TextColumn::make('importer')
                    ->label('导表工具'),
                TextColumn::make('total_rows')
                    ->label('总行数'),
                TextColumn::make('processed_rows')
                    ->label('处理完成'),
                TextColumn::make('successful_rows')
                    ->label('成功行数'),
                TextColumn::make('user.name')
                    ->label('用户'),
                TextColumn::make('completed_at')
                    ->label('完成时间'),
                TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
