<?php

namespace App\Filament\Actions\BlockChain;

use App\Enums\BlockChain\CertificateType;
use App\Models\BlockChain\Certificate;
use App\Services\BlockChain\CertificateService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class SignCertificateAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->label('签发证书');
        $this->icon(Heroicon::PencilSquare);
        $this->modalWidth(Width::Large);
        $this->visible(fn (Certificate $certificate): bool => userCan(self::getDefaultName(),
            $certificate) && $certificate->type === CertificateType::Certificate && $certificate->isDisabled());
        $this->modalHeading('选择中间证书并签发');
        $this->schema([
            Forms\Components\Select::make('intermediate_id')
                ->label('中间证书')
                ->options(fn () => Certificate::ofEnabled()->where('type', CertificateType::Intermediate)->pluck('common_name', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('passphrase')
                ->label('中间证书密码')
                ->password()
                ->revealable()
                ->required(),
            Forms\Components\TextInput::make('days')
                ->label('有效天数')
                ->numeric()
                ->default(365)
                ->required(),
        ]);

        $this->action(function (array $data, Certificate $certificate, CertificateService $service): void {
            try {
                $service->signCertificate(
                    $certificate,
                    Certificate::find($data['intermediate_id']),
                    $data['passphrase'],
                    $data['days']
                );

                $this->successNotificationTitle('签发成功');
                $this->success();
            } catch (Exception $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'signCertificate';
    }
}
