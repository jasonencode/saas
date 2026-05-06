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
        $this->visible(fn (Certificate $certificate): bool => userCan(self::getDefaultName(), $certificate) && $certificate->type === CertificateType::Intermediate && $certificate->isDisabled());
        $this->schema([
            Forms\Components\Select::make('ca_id')
                ->label('根证书')
                ->required()
                ->options(fn () => Certificate::ofEnabled()->where('type', CertificateType::CA)->pluck('common_name', 'id')),
            Forms\Components\TextInput::make('passphrase')
                ->label('根证书密码')
                ->password()
                ->revealable()
                ->required(),
            Forms\Components\TextInput::make('days')
                ->label('有效天数')
                ->integer()
                ->default(3650)
                ->required()
                ->helperText('证书有效期，不能超过根证书有效期'),
        ]);

        $this->action(function (array $data, Certificate $certificate, CertificateService $service): void {
            try {
                $service->signIntermediate(
                    $certificate,
                    Certificate::find($data['ca_id']),
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
}
