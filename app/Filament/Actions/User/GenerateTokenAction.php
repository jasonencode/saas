<?php

namespace App\Filament\Actions\User;

use App\Models\User\User;
use App\Services\System\EventLogService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class GenerateTokenAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generateToken';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('获取令牌');
        $this->icon(Heroicon::OutlinedKey);
        $this->color('warning');

        $this->visible(fn (User $record): bool => userCan(self::getDefaultName(), $record));

        $this->requiresConfirmation();
        $this->modalHeading('签发访问令牌');
        $this->modalDescription(sprintf(
            '令牌有效期为 %d 天，仅展示一次；签发行为会写入审计日志。',
            (int) ceil(((int) config('sanctum.expiration')) / 1440),
        ));
        $this->modalSubmitActionLabel('签发');

        $this->action(function (User $record): void {
            $expiresAt = now()->addMinutes((int) config('sanctum.expiration'));
            $token = $record->createToken('T:0', ['*'], $expiresAt);

            service(EventLogService::class)->write(
                'user.token_generated',
                '签发访问令牌',
                Filament::auth()->user(),
                $record,
                null,
                ['expires_at' => $expiresAt->toDateTimeString()],
            );

            Notification::make()
                ->title('访问令牌已生成（仅展示一次）')
                ->body($token->plainTextToken)
                ->persistent()
                ->send();

            $this->successNotificationTitle('令牌已签发并记录审计');
            $this->success();
        });
    }
}
