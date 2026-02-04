<?php

namespace App\Filament\Infolists\Components;

use Closure;
use Filament\Infolists\Components\Entry;
use Illuminate\Database\Eloquent\Model;

class TextareaEntry extends Entry
{
    protected string $view = 'filament.infolists.textarea-entry';

    protected int|Closure|null $rows = 10;

    public function rows(int|Closure|null $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function getRows()
    {
        return $this->evaluate($this->rows);
    }

    public function getState(): mixed
    {
        if ($this->hasConstantState) {
            $state = $this->evaluate($this->getConstantStateUsing);
        } else {
            $containerState = $this->getContainer()->getConstantState();

            $state = $containerState instanceof Model ?
                $this->getConstantStateFromRecord($containerState) :
                data_get($containerState, $this->getConstantStatePath());
        }
        if (is_array($state)) {
            $state = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($state) && ($separator = $this->getSeparator())) {
            $state = explode($separator, $state);
            $state = (count($state) === 1 && blank($state[0])) ?
                [] :
                $state;
        }

        if (blank($state)) {
            $state = $this->getDefaultState();
        }

        return $state;
    }
}
