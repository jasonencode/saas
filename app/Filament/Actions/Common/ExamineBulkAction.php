<?php

namespace App\Filament\Actions\Common;

use App\Enums\ExamineState;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Collection;

class ExamineBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'examineAny';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量审核');
        $this->icon('heroicon-c-power');
        $this->requiresConfirmation();
        $this->successNotificationTitle('审核操作成功');
        $this->deselectRecordsAfterCompletion();

        $this->visible(fn(HasTable $livewire) => userCan('examineAny', $livewire->getTable()->getModel()));

        $this->hidden(function(HasTable $livewire): bool {
            $trashedFilterState = $livewire->getTableFilterState(TrashedFilter::class) ?? [];
            if (!array_key_exists('value', $trashedFilterState)) {
                return false;
            }
            if ($trashedFilterState['value']) {
                return false;
            }

            return filled($trashedFilterState['value']);
        });

        $this->fillForm([
            'state' => ExamineState::Approved,
        ]);
        $this->form([
            Radio::make('state')
                ->label('审核结果')
                ->live()
                ->required()
                ->inline()
                ->inlineLabel(false)
                ->options(ExamineState::class)
                ->disableOptionWhen(fn(string $value): bool => $value === ExamineState::Pending->value),
            Textarea::make('text')
                ->label(fn(Get $get) => $get('state') === ExamineState::Rejected ? '驳回原因' : '通过备注')
                ->rows(4)
                ->required(),
            TextInput::make('password')
                ->required()
                ->password()
                ->label('操作密码')
                ->currentPassword(),
        ]);
        $this->action(function(): void {
            $this->process(function(Collection $records, array $data) {
                foreach ($records as $record) {
                    $examine = $record->examines()->latest()->first();
                    if ($data['state'] === ExamineState::Approved) {
                        $examine->pass(auth()->user(), $data['text']);
                    } else {
                        $examine->reject(auth()->user(), $data['text']);
                    }
                }
            });

            $this->success();
        });
    }
}
