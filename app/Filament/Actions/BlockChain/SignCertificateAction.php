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

class SignCertificateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'signCertificate';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->label('签发证书');
        $this->icon(Heroicon::PencilSquare);
        $this->modalWidth(Width::Large);
        $this->visible(fn (Certificate $certificate) => $certificate->type === CertificateType::Certificate && $certificate->isDisabled());
        $this->modalHeading('选择中间证书并签发');
        $this->schema([
            Select::make('intermediate_id')
                ->label('中间证书')
                ->options(fn () => Certificate::ofEnabled()->where('type', CertificateType::Intermediate)->pluck('common_name', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('passphrase')
                ->label('中间证书密码')
                ->password()
                ->revealable()
                ->required(),
            TextInput::make('days')
                ->label('有效天数')
                ->numeric()
                ->default(365)
                ->required(),
        ]);

        $this->action(function (array $data, Certificate $certificate): void {
            $intermediate = Certificate::find($data['intermediate_id']);
            if ($intermediate->updated_at->addDays($intermediate->days)->isBefore(now()->addDays($data['days']))) {
                $this->failureNotificationTitle('中间证书有效期不能超过根证书有效期');
                $this->failure();

                return;
            }

            if ($intermediate->password !== $data['passphrase']) {
                $this->failureNotificationTitle('根证书密码错误');
                $this->failure();

                return;
            }

            $pk = $certificate->sign_type->getPrivateKey();
            $keyPair = $pk->export();

            $csr = CertificateSigningRequest::make(
                $certificate->dn,
                $keyPair->getPrivateKeyResource(),
                $pk->getOptions()
            );

            $cert = CertificateSigningRequest::sign(
                $csr,
                openssl_pkey_get_private($intermediate->private_key, $intermediate['password']),
                openssl_x509_read($intermediate->certificate),
                $data['days'],
                $pk->getOptions()
            );

            $certificate->parent_id = $data['intermediate_id'];
            $certificate->csr = $csr;
            $certificate->private_key = $keyPair->getPrivateKey();
            $certificate->certificate = $cert;
            $certificate->days = $data['days'];
            $certificate->status = true;
            $certificate->save();

            $this->successNotificationTitle('签发成功');
            $this->success();
        });
    }
}
