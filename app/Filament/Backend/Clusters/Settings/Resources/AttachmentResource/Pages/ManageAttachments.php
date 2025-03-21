<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\AttachmentResource\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\AttachmentResource;
use App\Models\Attachment;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables;
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
                Tables\Columns\TextColumn::make('name')
                    ->label('原始文件名/HASH')
                    ->description(fn(Attachment $record): string => 'HASH: '.$record->hash),
                Tables\Columns\TextColumn::make('mime')
                    ->description(fn(Attachment $record): string => $record->extension)
                    ->label('文件类型/扩展名'),
                Tables\Columns\TextColumn::make('size')
                    ->label('文件大小')
                    ->formatStateUsing(fn($state) => formatBytes((int) $state)),
                Tables\Columns\TextColumn::make('disk')
                    ->label('存储磁盘'),
                Tables\Columns\TextColumn::make('path')
                    ->label('存储路径'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
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
