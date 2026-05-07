<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Product;
use App\Services\Mall\ProductService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ProductUpAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('上架');
        $this->icon(Heroicon::OutlinedArrowUpCircle);
        $this->color('success');
        $this->visible(fn (Product $record): bool => userCan(self::getDefaultName(), $record) && $record->status === ProductStatus::Down);
        $this->requiresConfirmation();
        $this->action(function (Product $record, ProductService $service): void {
            $service->up($record);

            $this->successNotificationTitle('上架成功');
            $this->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'productUp';
    }
}
