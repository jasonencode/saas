<?php

namespace App\Filament\Actions\Mall;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class UpProductAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'up';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('上架');
        $this->icon(Heroicon::OutlinedArrowUpCircle);
        $this->color('success');
        $this->visible(fn (Product $record) => $record->status === ProductStatus::Down);
        $this->requiresConfirmation();
        $this->action(function (Product $record) {
            $record->update(['status' => ProductStatus::Up]);

            $this->successNotificationTitle('上架成功');
            $this->success();
        });
    }
}
