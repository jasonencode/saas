<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Certificates\Tables;

use App\Filament\Actions\BlockChain\CertificateDownloadAction;
use App\Filament\Actions\BlockChain\CertificateInfoAction;
use App\Filament\Actions\BlockChain\SignCaAction;
use App\Filament\Actions\BlockChain\SignCertificateAction;
use App\Filament\Actions\BlockChain\SignIntermediateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户')
                    ->badge(),
                Tables\Columns\TextColumn::make('type')
                    ->label('证书类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('parent.common_name')
                    ->label('签发机构')
                    ->badge(),
                Tables\Columns\TextColumn::make('common_name')
                    ->label('证书名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('days')
                    ->label('有效期')
                    ->suffix('天'),
                Tables\Columns\TextColumn::make('sign_type')
                    ->label('签名算法'),
                Tables\Columns\IconColumn::make('status')
                    ->label('签发状态'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                SignCaAction::make(),
                SignIntermediateAction::make(),
                SignCertificateAction::make(),
                CertificateInfoAction::make(),
                CertificateDownloadAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
