<?php

namespace App\Filament\Backend\Clusters\Contents\Resources;

use App\Filament\Backend\Clusters\Contents;
use App\Filament\Backend\Clusters\Contents\Resources\SensitiveResource\Pages\ManageSensitives;
use App\Models\Sensitive;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SensitiveResource extends Resource
{
    protected static ?string $model = Sensitive::class;

    protected static ?string $modelLabel = '敏感词';

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = '敏感词管理';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Contents::class;

    public static function getPages(): array
    {
        return [
            'index' => ManageSensitives::route('/'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('keywords')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull()
                    ->label('敏感词'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('keywords')
                    ->label('敏感词'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
