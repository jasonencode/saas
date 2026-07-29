<?php

namespace App\Filament\Actions\Tenant;

use App\Models\System\Tenant;
use App\Services\User\TenantService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class RenewalAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'renewal';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->label('租户续期');
        $this->icon(Heroicon::OutlinedCalendarDateRange);
        $this->visible(fn (Tenant $tenant): bool => userCan(self::getDefaultName(), $tenant));
        $this->requiresConfirmation();

        $this->fillForm(function (Tenant $tenant): array {
            return [
                'expired_at' => $tenant->expired_at->addYear(),
            ];
        });
        $this->schema([
            DatePicker::make('expired_at')
                ->label('到期时间')
                ->required()
                ->minDate(now())
                ->displayFormat('Y-m-d')
                ->hintActions([
                    Action::make('extend_3_years')
                        ->label('三年')
                        ->icon(Heroicon::OutlinedCalendar)
                        ->action(function (Set $set, ?string $state, Model $record): void {
                            $baseDate = filled($state) ? Carbon::parse($state) : $record->expired_at;
                            $set('expired_at', $baseDate->addYears(3)->format('Y-m-d'));
                        }),
                    Action::make('extend_10_years')
                        ->label('十年')
                        ->icon(Heroicon::OutlinedCalendar)
                        ->action(function (Set $set, ?string $state, Model $record): void {
                            $baseDate = filled($state) ? Carbon::parse($state) : $record->expired_at;
                            $set('expired_at', $baseDate->addYears(10)->format('Y-m-d'));
                        }),
                ]),
        ]);

        $this->action(function (Tenant $tenant, array $data): void {
            service(TenantService::class)->renew($tenant, $data['expired_at']);
            $this->successNotificationTitle('租户续期成功');
            $this->success();
        });
    }
}
