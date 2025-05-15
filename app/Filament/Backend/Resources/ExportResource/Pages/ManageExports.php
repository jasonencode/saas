<?php

namespace App\Filament\Backend\Resources\ExportResource\Pages;

use App\Filament\Backend\Resources\ExportResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ManageExports extends ManageRecords
{
    protected static string $resource = ExportResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('文件名称'),
                Tables\Columns\TextColumn::make('file_disk')
                    ->label('存储磁盘'),
                Tables\Columns\TextColumn::make('exporter')
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
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
