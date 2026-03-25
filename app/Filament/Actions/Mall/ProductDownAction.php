<?php

namespace App\Filament\Actions\Mall;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ProductDownAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'productDown';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('下架');
        $this->icon(Heroicon::OutlinedArrowDownCircle);
        $this->color('warning');
        $this->visible(fn (Product $record) => $record->status === ProductStatus::Up);
        $this->requiresConfirmation();
        $this->action(function (Product $record, ProductService $service) {
            $service->down($record);

            $this->successNotificationTitle('下架成功');
            $this->success();
        });
    }
}
