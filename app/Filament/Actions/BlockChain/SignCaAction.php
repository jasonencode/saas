<?php

namespace App\Filament\Actions\BlockChain;

use App\Enums\BlockChain\CertificateType;
use App\Extensions\Certificate\CertificateSigningRequest;
use App\Models\BlockChain\Certificate;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class SignCaAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'caSign';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('签发CA证书');
        $this->icon(Heroicon::PencilSquare);
        $this->requiresConfirmation();
        $this->visible(fn (Certificate $certificate) => $certificate->type === CertificateType::CA && $certificate->isDisabled());

        $this->action(function (Certificate $certificate) {
            $pk = $certificate->sign_type->getPrivateKey();
            $keyPair = $pk->password($certificate->password)->export();

            $csr = CertificateSigningRequest::make(
                $certificate->dn,
                $keyPair->getPrivateKeyResource($certificate->password),
                $pk->getOptions()
            );

            $cert = CertificateSigningRequest::selfSignCaCert(
                $csr,
                $keyPair->getPrivateKeyResource($certificate->password),
                $certificate->days,
                $pk->getOptions()
            );

            $certificate->csr = $csr;
            $certificate->certificate = $cert;
            $certificate->private_key = $keyPair->getPrivateKey();
            $certificate->status = true;
            $certificate->save();

            $this->successNotificationTitle('根证书签发成功');
            $this->success();
        });
    }
}