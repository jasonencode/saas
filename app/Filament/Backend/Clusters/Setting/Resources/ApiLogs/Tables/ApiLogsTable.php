<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ApiLogs\Tables;

use App\Enums\HttpMethod;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
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
                Tables\Columns\TextColumn::make('path')
                    ->label('请求地址')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP地址')
                    ->copyable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        if (filter_var($search, FILTER_VALIDATE_IP)) {
                            return $query->where('ip', $search);
                        }

                        if (str_contains($search, '/') && preg_match('/^\d{1,3}(\.\d{1,3}){3}\/\d{1,2}$/', $search)) {
                            return $query->where('ip', '<<', $search);
                        }

                        return $query->where('ip', 'like', "%$search%");
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
                Tables\Filters\Filter::make('ip')
                    ->schema([
                        TextInput::make('ip')
                            ->label('IP地址(支持CIDR)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['ip'],
                            function (Builder $query, $ip): Builder {
                                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                                    return $query->where('ip', $ip);
                                }

                                if (str_contains($ip, '/') && preg_match('/^\d{1,3}(\.\d{1,3}){3}\/\d{1,2}$/', $ip)) {
                                    return $query->where('ip', '<<', $ip);
                                }

                                return $query->where('ip', 'like', "%{$ip}%");
                            }
                        );
                    }),
                Tables\Filters\Filter::make('status_code')
                    ->schema([
                        TextInput::make('code')
                            ->label('状态码'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['code'],
                            fn (Builder $query, $code): Builder => $query->where('status_code', $code)
                        );
                    }),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
