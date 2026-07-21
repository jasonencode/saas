<?php

namespace App\Filament\Actions\Common;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class HeaderSubmitAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'headerSubmit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('保存提交');
        $this->color('primary');
        $this->icon(Heroicon::OutlinedCheckBadge);
        $this->formId('form');
        $this->submit('form');
    }

    public function getFormToSubmit(): ?string
    {
        return $this->resolveSubmitHandler();
    }

    public function getLivewireTarget(): ?string
    {
        return $this->resolveSubmitHandler();
    }

    protected function resolveSubmitHandler(): string
    {
        return $this->getLivewire() instanceof EditRecord ? 'save' : 'create';
    }
}
