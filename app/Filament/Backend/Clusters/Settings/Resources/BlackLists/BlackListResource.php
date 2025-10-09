<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\BlackLists;

use App\Filament\Backend\Clusters\Settings\Resources\BlackLists\Pages\ManageBlackLists;
use App\Filament\Backend\Clusters\Settings\SettingsCluster;
use App\Models\BlackList;
use App\Rules\IpOrCidr;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlackListResource extends Resource
{
    protected static ?string $model = BlackList::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::InboxStack;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $modelLabel = 'IP黑名单';

    protected static ?string $navigationLabel = '黑名单管理';

    protected static ?int $navigationSort = 91;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
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

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP地址')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
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
            'index' => ManageBlackLists::route('/'),
        ];
    }
}
