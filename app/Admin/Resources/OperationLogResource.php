<?php

namespace App\Admin\Resources;

use App\Admin\Resources\OperationLogResource\Pages\ManageOperationLogs;
use App\Models\OperationLog;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OperationLogResource extends Resource
{
    protected static ?string $model = OperationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = '操作日志';

    protected static ?string $navigationGroup = '扩展';

    protected static ?int $navigationSort = 100;

    public static function infolist(Infolist $infolist): Infolist
    {
        return parent::infolist($infolist);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('method')
                    ->label('请求类型')
                    ->icon('heroicon-o-link')
                    ->badge()
                    ->description(fn($record) => '('.$record->status.') '.str($record->url)->remove(url('/')))
                    ->searchable(),
                TextColumn::make('remote_address')
                    ->label('请求地址')
                    ->description(fn($record) => $record->model?->name)
                    ->icon('heroicon-o-globe-alt')
                    ->searchable(),
                TextColumn::make('response_time')
                    ->label('响应时间')
                    ->icon('heroicon-o-clock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->description(fn($record) => $record->created_at->diffForHumans())
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('method')
                    ->label('请求类型')
                    ->searchable()
                    ->options([
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                        'DELETE' => 'DELETE',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => ManageOperationLogs::route('/'),
        ];
    }
}
