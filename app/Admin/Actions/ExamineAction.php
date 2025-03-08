<?php

namespace App\Admin\Actions;

use App\Contracts\ShouldExamine;
use App\Enums\ExamineState;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TrashedFilter;

class ExamineAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'examine';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('审核');
        $this->modalHeading(fn(HasTable $livewire) => $livewire->getTable()->getModelLabel().'审核');
        $this->icon('heroicon-o-wrench-screwdriver');
        $this->hidden(function(ShouldExamine $record, HasTable $livewire): bool {
            $trashedFilterState = $livewire->getTableFilterState(TrashedFilter::class) ?? [];

            if (array_key_exists('value', $trashedFilterState) && $trashedFilterState['value']) {
                return true;
            }
            $examine = $record->examines()->latest()->first();

            return $examine?->state == ExamineState::Approved;
        });
        $this->visible(fn(HasTable $livewire) => userCan('examine', $livewire->getTable()->getModel()));
        $this->fillForm(function(ShouldExamine $record) {
            return [
                'state' => ExamineState::Approved,
                'pending_text' => $record->examines()->latest()->first()?->pending_text,
            ];
        });
        $this->form([
            Textarea::make('pending_text')
                ->label('申请说明')
                ->rows(4)
                ->disabled()
                ->readOnly(),
            Radio::make('state')
                ->label('审核结果')
                ->live()
                ->required()
                ->inline()
                ->inlineLabel(false)
                ->options(ExamineState::class)
                ->disableOptionWhen(fn(string $value): bool => $value === ExamineState::Pending->value),
            Textarea::make('text')
                ->label(fn(Get $get) => $get('state') == ExamineState::Rejected ? '驳回原因' : '通过备注')
                ->rows(4)
                ->required(),
            TextInput::make('password')
                ->label('当前密码')
                ->password()
                ->required()
                ->currentPassword(),
        ]);
        $this->action(function(ShouldExamine $record, array $data): void {
            $examine = $record->examines()->latest()->first();
            if ($data['state'] == ExamineState::Approved) {
                $result = $this->process(fn() => $examine->pass(auth()->user(), $data['text']));
            } else {
                $result = $this->process(fn() => $examine->reject(auth()->user(), $data['text']));
            }
            if (!$result) {
                $this->failure();

                return;
            }
            $this->success();
        });
    }
}
