<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\AttachmentResource\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\AttachmentResource;
use App\Models\Attachment;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageAttachments extends ManageRecords
{
    protected static string $resource = AttachmentResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function(Builder $query): Builder {
                return $query->latest();
            })
            ->columns([
                TextColumn::make('name')
                    ->label('原始文件名/HASH')
                    ->description(fn(Attachment $record): string => 'HASH: '.$record->hash),
                TextColumn::make('mime')
                    ->description(fn(Attachment $record): string => $record->extension)
                    ->label('文件类型/扩展名'),
                TextColumn::make('size')
                    ->label('文件大小')
                    ->formatStateUsing(fn($state) => formatBytes((int) $state)),
                TextColumn::make('disk')
                    ->label('存储磁盘'),
                TextColumn::make('path')
                    ->label('存储路径'),
                TextColumn::make('created_at')
                    ->translateLabel(),
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
