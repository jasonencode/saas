<?php

namespace App\Filament\Actions\Mall;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DownProductAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'down';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('下架');
        $this->icon(Heroicon::OutlinedArrowDownCircle);
        $this->color('warning');
        $this->visible(fn(Product $record) => $record->status === ProductStatus::Up);
        $this->requiresConfirmation();
        $this->action(function (Product $record) {
            $record->update(['status' => ProductStatus::Down]);

            $this->successNotificationTitle('下架成功');
            $this->success();
        });
    }
}
