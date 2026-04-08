<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Product;
use App\Services\ProductService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ProductUpAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'productUp';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('上架');
        $this->icon(Heroicon::OutlinedArrowUpCircle);
        $this->color('success');
        $this->visible(fn (Product $record) => $record->status === ProductStatus::Down);
        $this->requiresConfirmation();
        $this->action(function (Product $record, ProductService $service) {
            $service->up($record);

            $this->successNotificationTitle('上架成功');
            $this->success();
        });
    }
}
