<?php

namespace App\Filament\Actions\BlockChain;

use App\Enums\CertificateType;
use App\Extensions\Certificate\CertificateSigningRequest;
use App\Models\Certificate;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class SignIntermediateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'signIntermediate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('签发证书');
        $this->icon(Heroicon::PencilSquare);
        $this->modalHeading('使用根证书签发');
        $this->modalWidth(Width::Large);
        $this->visible(fn(Certificate $certificate) => $certificate->type === CertificateType::Intermediate && $certificate->isDisabled());
        $this->schema([
            Select::make('ca_id')
                ->label('根证书')
                ->required()
                ->options(fn() => Certificate::ofEnabled()->where('type', CertificateType::CA)->pluck('common_name', 'id')),
            TextInput::make('passphrase')
                ->label('根证书密码')
                ->password()
                ->revealable()
                ->required(),
            TextInput::make('days')
                ->label('有效天数')
                ->integer()
                ->default(3650)
                ->required()
                ->helperText('证书有效期，不能超过根证书有效期'),
        ]);

        $this->action(function (array $data, Certificate $intermediate) {
            $CA = Certificate::find($data['ca_id']);

            if ($CA->updated_at->addDays($CA->days)->isBefore(now()->addDays($data['days']))) {
                $this->failureNotificationTitle('中间证书有效期不能超过根证书有效期');
                $this->failure();

                return;
            }

            if ($CA->password != $data['passphrase']) {
                $this->failureNotificationTitle('根证书密码错误');
                $this->failure();

                return;
            }

            $pk = $intermediate->sign_type->getPrivateKey();
            $keyPair = $pk->password($intermediate->password)->export();

            $csr = CertificateSigningRequest::make(
                $intermediate->dn,
                $keyPair->getPrivateKeyResource($intermediate->password),
                $pk->getOptions()
            );

            $cert = CertificateSigningRequest::sign(
                $csr,
                openssl_pkey_get_private($CA->private_key, $CA['password']),
                openssl_x509_read($CA->certificate),
                $data['days'],
                $pk->getOptions()
            );

            $intermediate->parent_id = $data['ca_id'];
            $intermediate->csr = $csr;
            $intermediate->private_key = $keyPair->getPrivateKey();
            $intermediate->certificate = $cert;
            $intermediate->days = $data['days'];
            $intermediate->status = true;
            $intermediate->save();

            $this->successNotificationTitle('签发成功');
            $this->success();
        });
    }
}
