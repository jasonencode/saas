<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\Comments\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Textarea::make('content')
                    ->label('评论内容')
                    ->rows(5)
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
            ]);
    }
}
