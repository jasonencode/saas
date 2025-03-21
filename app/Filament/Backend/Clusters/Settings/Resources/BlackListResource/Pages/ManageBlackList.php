<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\BlackListResource\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\BlackListResource;
use App\Rules\IpOrCidr;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ManageBlackList extends ManageRecords
{
    protected static string $resource = BlackListResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('ip')
                    ->label('IP/CIDR')
                    ->helperText('支持单独IP:172.16.1.1或CIDR:172.16.0.0/16格式')
                    ->rules(['required', new IpOrCidr])
                    ->required(),
                Forms\Components\Textarea::make('remark')
                    ->label('备注')
                    ->rows(4),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP地址')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
