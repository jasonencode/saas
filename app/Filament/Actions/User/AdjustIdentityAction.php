<?php

namespace App\Filament\Actions\User;

use App\Enums\User\IdentityChannel;
use App\Models\User\Identity;
use App\Models\User\User;
use App\Services\User\IdentityService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class AdjustIdentityAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'adjustIdentity';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('身份调整');
        $this->icon(Heroicon::OutlinedArrowsRightLeft);
        $this->modalWidth(Width::Medium);
        $this->visible(fn (User $record): bool => userCan(self::getDefaultName(), $record));
        $this->schema([
            Forms\Components\Select::make('identity_id')
                ->label('目标身份')
                ->options(function (User $record): array {
                    $tenant = Filament::getTenant();

                    $query = Identity::query();

                    if ($tenant) {
                        $query->whereBelongsTo($tenant);
                    } elseif ($record->tenant_id) {
                        $query->where('tenant_id', $record->tenant_id);
                    }

                    if (config('custom.identity.allow_multiple')) {
                        $query->where('can_subscribe', true)->where('status', true);
                    }

                    return $query->pluck('name', 'id')->toArray();
                })
                ->searchable()
                ->required(),
        ]);

        $this->action(function (User $record, array $data): void {
            /** @var IdentityService $identityService */
            $identityService = app(IdentityService::class);
            /** @var Identity $identity */
            $identity = Identity::when(
                Filament::getTenant(),
                static fn ($q) => $q->whereBelongsTo(Filament::getTenant()),
                static fn ($q) => $q->where('tenant_id', $record->tenant_id),
            )
                ->findOrFail($data['identity_id']);

            try {
                $identityService->entry(
                    $record,
                    $identity,
                    IdentityChannel::System,
                );

                $this->successNotificationTitle('身份调整成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('身份调整失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
