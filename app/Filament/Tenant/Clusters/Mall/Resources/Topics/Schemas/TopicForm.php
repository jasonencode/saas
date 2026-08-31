<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Topics\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('专题名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('专题标识')
                    ->required()
                    ->unique(
                        modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', Filament::getTenant()->getKey())
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
