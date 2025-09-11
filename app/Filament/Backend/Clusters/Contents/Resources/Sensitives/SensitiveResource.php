<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Sensitives;

use App\Filament\Backend\Clusters\Contents\ContentsCluster;
use App\Filament\Backend\Clusters\Contents\Resources\Sensitives\Pages\ManageSensitives;
use App\Models\Sensitive;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class SensitiveResource extends Resource
{
    protected static ?string $model = Sensitive::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $cluster = ContentsCluster::class;

    protected static ?string $modelLabel = '敏感词';

    protected static ?string $navigationLabel = '敏感词管理';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->label('创建时间'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSensitives::route('/'),
        ];
    }
}
