<?php

namespace App\Filament\Actions\Mall;

use App\Models\Mall\ReturnAddress;
use App\Services\Mall\StoreService;
use Filament\Actions\Action;

class StoreSetDefaultReturnAddressAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'storeSetDefaultReturnAddress';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('设为默认地址');
        $this->visible(fn (ReturnAddress $address): bool => userCan(self::getDefaultName(), $address));
        $this->hidden(fn (ReturnAddress $address): bool => $address->is_default);
        $this->requiresConfirmation();
        $this->action(function (ReturnAddress $address, StoreService $service): void {
            $service->setDefaultReturnAddress($address);

            $this->successNotificationTitle('设置成功');
            $this->success();
        });
    }
}
