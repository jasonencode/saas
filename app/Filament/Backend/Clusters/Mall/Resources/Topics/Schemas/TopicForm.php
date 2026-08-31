<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Topics\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Forms\Components\TenantSelect;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Query\Builder;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make(),
                Forms\Components\TextInput::make('name')
                    ->label('专题名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('专题标识')
                    ->required()
                    ->unique(
                        modifyRuleUsing: fn (Builder $rule, Get $get) => $rule->where('tenant_id', $get('tenant_id'))
                    )
                    ->maxLength(64)
                    ->helperText('用于 URL')
                    ->hintActions([
                        Action::make('new')
                            ->label('新品')
                            ->action(fn (Set $set) => $set('slug', 'new')),
                        Action::make('sale')
                            ->label('热销')
                            ->action(fn (Set $set) => $set('slug', 'sale')),
                        Action::make('quality')
                            ->label('优选')
                            ->action(fn (Set $set) => $set('slug', 'quality')),
                    ]),
                Forms\Components\Textarea::make('description')
                    ->label('专题简介')
                    ->rows(3)
                    ->maxLength(500),
                CustomUpload::cover()
                    ->required(),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->required()
                    ->integer()
                    ->default(0)
                    ->helperText('数字越大越靠前'),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
            ]);
    }
}
