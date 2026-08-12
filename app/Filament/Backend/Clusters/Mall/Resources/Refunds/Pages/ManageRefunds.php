<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Pages;

use App\Enums\Mall\RefundStatus;
use App\Filament\Backend\Clusters\Mall\Resources\Refunds\RefundResource;
use App\Models\Mall\Refund;
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
                ->label(RefundStatus::Pending->getLabel())
                ->badge(fn () => Refund::ofPending()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofPending()),
            'waiting_return' => Tab::make()
                ->label(RefundStatus::WaitingReturn->getLabel())
                ->badge(fn () => Refund::where('status', RefundStatus::WaitingReturn)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', RefundStatus::WaitingReturn)),
            'shipping' => Tab::make()
                ->label(RefundStatus::Shipping->getLabel())
                ->badge(fn () => Refund::where('status', RefundStatus::Shipping)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', RefundStatus::Shipping)),
            'received' => Tab::make()
                ->label(RefundStatus::Received->getLabel())
                ->badge(fn () => Refund::where('status', RefundStatus::Received)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', RefundStatus::Received)),
            'processing' => Tab::make()
                ->label(RefundStatus::Processing->getLabel())
                ->badge(fn () => Refund::ofProcessing()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofProcessing()),
            'completed' => Tab::make()
                ->label(RefundStatus::Completed->getLabel())
                ->badge(fn () => Refund::ofCompleted()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofCompleted()),
            'rejected' => Tab::make()
                ->label(RefundStatus::Rejected->getLabel())
                ->badge(fn () => Refund::ofRejected()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofRejected()),
            'cancelled' => Tab::make()
                ->label(RefundStatus::Cancelled->getLabel())
                ->badge(fn () => Refund::ofCancelled()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofCancelled()),
            'failed' => Tab::make()
                ->label(RefundStatus::Failed->getLabel())
                ->badge(fn () => Refund::ofFailed()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofFailed()),
        ];
    }
}
