<?php

namespace App\Filament\Actions\User;

use App\Enums\RealnameStatus;
use App\Models\UserRealname;
use App\Services\RealnameService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class RejectRealnameAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'rejectRealname';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('拒绝');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');
        $this->modalWidth(Width::Medium);
        $this->modalHeading(fn () => '拒绝实名认证');
        $this->schema([
            Textarea::make('reject_reason')
                ->label('拒绝原因')
                ->required(),
        ]);
        $this->hidden(fn (UserRealname $record): bool => $record->status !== RealnameStatus::Pending);

        $this->action(function (UserRealname $record, array $data) {
            app(RealnameService::class)->reject($record, $data['reject_reason']);
            $this->success();
        });
    }
}
