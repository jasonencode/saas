<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ApiLogs\Tables;

use App\Enums\HttpMethod;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApiLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('用户'),
                Tables\Columns\TextColumn::make('method')
                    ->label('请求类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('url')
                    ->label('请求地址')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP地址')
                    ->copyable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('ip', 'like', "%{long2ip($search)}%");
                    }),
                Tables\Columns\TextColumn::make('status_code')
                    ->label('返回状态码'),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('耗时')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('请求时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->label('请求方式')
                    ->native(false)
                    ->options(HttpMethod::class),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
