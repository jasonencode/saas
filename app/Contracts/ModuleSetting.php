<?php

namespace App\Contracts;

use Filament\Forms\Components\Tabs\Tab;

abstract class ModuleSetting
{
    abstract public function casts(): array;

    abstract public function tab(): Tab;

    protected function convertConfigName(string $name): string
    {
        return sprintf('%s.%s', $this->name(), $name);
    }

    abstract public function name(): string;
}