<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\BlackLists\Tables;

use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlackListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP地址')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        if (filter_var($search, FILTER_VALIDATE_IP)) {
                            return $query->where('ip', $search);
                        }

                        if (str_contains($search, '/') && preg_match('/^\d{1,3}(\.\d{1,3}){3}\/\d{1,2}$/', $search)) {
                            return $query->where('ip', '<<', $search);
                        }

                        return $query->where('ip', 'like', "%$search%");
                    })
                    ->copyable(),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('ip')
                    ->schema([
                        TextInput::make('ip')
                            ->label('IP/CIDR'),
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
}
