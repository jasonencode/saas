<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\BlackListResource\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\BlackListResource;
use App\Rules\IpOrCidr;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManageBlackList extends ManageRecords
{
    protected static string $resource = BlackListResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                TextInput::make('ip')
                    ->label('IP/CIDR')
                    ->helperText('支持单独IP:172.16.1.1或CIDR:172.16.0.0/16格式')
                    ->rules(['required', new IpOrCidr])
                    ->required(),
                Textarea::make('remark')
                    ->label('备注')
                    ->rows(4),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ip')
                    ->label('IP地址')
                    ->searchable(),
                TextColumn::make('remark')
                    ->label('备注')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
