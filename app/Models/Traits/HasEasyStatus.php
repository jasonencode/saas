<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasEasyStatus
{
    public function initializeHasEasyStatus(): void
    {
        $this->mergeCasts([
            $this->getStatusField() => 'boolean',
        ]);
    }

    protected function getStatusField(): string
    {
        return $this->statusField ?? 'status';
    }

    #[Scope]
    public function ofEnabled(Builder $query): Builder
    {
        return $query->where($this->getStatusField(), true);
    }

    #[Scope]
    public function ofDisabled(Builder $query): Builder
    {
        return $query->where($this->getStatusField(), false);
    }

    public function toggleStatus(): bool
    {
        return $this->isEnabled() ? $this->disable() : $this->enable();
    }

    public function isEnabled(): bool
    {
        return (bool) $this->{$this->getStatusField()};
    }

    public function disable(): bool
    {
        $this->{$this->getStatusField()} = false;

        return $this->save();
    }

    public function enable(): bool
    {
        $this->{$this->getStatusField()} = true;

        return $this->save();
    }

    public function canEnable(): bool
    {
        return $this->isDisabled();
    }

    public function isDisabled(): bool
    {
        return !$this->isEnabled();
    }

    public function canDisable(): bool
    {
        return $this->isEnabled();
    }
}
