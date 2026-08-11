<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Tags\Schemas;

use App\Enums\Content\TagType;
use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make()
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('type')
                    ->default(TagType::Content),
                Forms\Components\TextInput::make('name')
                    ->label('标签名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->required()
                    ->helperText('数字越大越靠前')
                    ->integer()
                    ->default(0),
            ]);
    }
}
