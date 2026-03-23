<?php

namespace App\Filament\Actions\Mall;

use App\Models\ReturnAddress;
use Filament\Actions\Action;

class SetDefaultReturnAddressAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'setDefaultReturnAddress';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('设为默认地址');

        $this->hidden(fn (ReturnAddress $address) => $address->is_default);
        $this->requiresConfirmation();
        $this->action(function (ReturnAddress $address) {
            $address->is_default = true;
            $address->save();

            $this->successNotificationTitle('设置成功');
            $this->success();
        });
    }
}