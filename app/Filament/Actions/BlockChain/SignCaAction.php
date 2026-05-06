<?php

namespace App\Filament\Actions\BlockChain;

use App\Enums\BlockChain\CertificateType;
use App\Models\BlockChain\Certificate;
use App\Services\BlockChain\CertificateService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class SignCaAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'signCa';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('签发CA证书');
        $this->icon(Heroicon::PencilSquare);
        $this->requiresConfirmation();
        $this->visible(fn (Certificate $certificate): bool => userCan(self::getDefaultName(), $certificate) && $certificate->type === CertificateType::CA && $certificate->isDisabled());

        $this->action(function (Certificate $certificate, CertificateService $service): void {
            $service->selfSignCaCert($certificate);

            $this->successNotificationTitle('根证书签发成功');
            $this->success();
        });
    }
}
