<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Tags\Schemas;

use App\Enums\Content\TagType;
use Filament\Forms;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Hidden::make('type')
                    ->default(TagType::Content),
                Forms\Components\TextInput::make('name')
                    ->label('标签名称')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
