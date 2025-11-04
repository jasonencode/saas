<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Examines\Pages;

use App\Filament\Backend\Clusters\Content\Resources\Examines\ExamineResource;
use App\Models\Examine;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageExamines extends ManageRecords
{
    protected static string $resource = ExamineResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('全部'),
            'pending' => Tab::make()
                ->label('待审核')
                ->badge(fn() => Examine::ofPending()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofPending()),
            'reject' => Tab::make()
                ->label('已驳回')
                ->badge(fn() => Examine::ofRejected()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofRejected()),
        ];
    }
}
