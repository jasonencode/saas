<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
use App\Models\Refund;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageRefunds extends ManageRecords
{
    protected static string $resource = RefundResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('全部'),
            'pending' => Tab::make()
                ->label('待处理')
                ->badge(fn () => Refund::ofPending()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofPending()),
            'processing' => Tab::make()
                ->label('处理中')
                ->badge(fn () => Refund::ofProcessing()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofProcessing()),
            'rejected' => Tab::make()
                ->label('拒绝退款')
                ->badge(fn () => Refund::ofRejected()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofRejected()),
            'cancelled' => Tab::make()
                ->label('取消申请')
                ->badge(fn () => Refund::ofCancelled()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofCancelled()),
            'completed' => Tab::make()
                ->label('已完成')
                ->badge(fn () => Refund::ofCompleted()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofCompleted()),
        ];
    }
}
