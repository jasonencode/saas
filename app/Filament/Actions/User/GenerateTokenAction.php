<?php

namespace App\Filament\Actions\User;

use App\Models\User\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Enums\Width;
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
        $this->modalWidth(Width::Medium);

        $this->visible(fn (User $record): bool => userCan(self::getDefaultName(), $record));

        $this->fillForm(fn (User $record) => ['token' => $record->createToken('T:0')->plainTextToken]);

        $this->schema([
            Forms\Components\TextInput::make('token')
                ->label('访问令牌')
                ->readOnly()
                ->copyable()
                ->columnSpanFull(),
        ]);

        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel('关闭');
    }
}
