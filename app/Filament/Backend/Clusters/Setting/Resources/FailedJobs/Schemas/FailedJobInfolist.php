<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\FailedJobs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FailedJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextEntry::make('id'),
                TextEntry::make('uuid'),
                TextEntry::make('connection'),
                TextEntry::make('queue'),

                TextEntry::make('payload')
                    ->formatStateUsing(function ($state) {
                        return json_decode($state, true)['displayName'];
                    })->label('Job'),
                TextEntry::make('failed_at')
                    ->placeholder('-'),
                TextEntry::make('payload')
                    ->formatStateUsing(function ($state) {
                        return '<pre style="overflow-x: auto; line-height: 2">'.htmlspecialchars(json_encode(json_decode($state, true, 512, JSON_THROW_ON_ERROR),
                                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)).'</pre>';
                    })
                    ->html()
                    ->columnSpanFull(),
                TextEntry::make('exception')
                    ->columnSpanFull()
                    ->formatStateUsing(function ($state) {
                        return '<div style="line-height: 2">'.nl2br($state).'</div>';
                    })
                    ->html(),
            ]);
    }
}