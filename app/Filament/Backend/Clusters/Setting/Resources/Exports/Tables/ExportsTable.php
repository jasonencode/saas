<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Exports\Tables;

use Filament\Actions;
use Filament\Actions\Exports\Models\Export;
use Filament\Tables;
use Filament\Tables\Table;

class ExportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
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
                    ->label('完成时间')
                    ->dateTime('Y-m-d H:i:s'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->recordActions([
                Actions\DeleteAction::make(),
                Actions\Action::make('download_xlsx')
                    ->label('下载XLSX')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(function (Export $record) {
                        return route('filament.exports.download', ['export' => $record, 'format' => 'xlsx']);
                    }, true)
                    ->visible(fn (Export $record) => $record->completed_at),
                Actions\Action::make('download_csv')
                    ->label('下载CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(function (Export $record) {
                        return route('filament.exports.download', ['export' => $record, 'format' => 'csv']);
                    }, true)
                    ->visible(fn (Export $record) => $record->completed_at),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
