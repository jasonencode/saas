<?php

namespace App\Tasks\Traits;

use App\Models\Finance\Task;

trait WithDefaultSetting
{
    public function __construct(protected Task $task)
    {
        $this->options = array_merge($this->options, $task->options ?? []);
    }

    public function getDefaultOptions(): array
    {
        return $this->options;
    }
}
