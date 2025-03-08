<?php

namespace App\Extensions\Workflow;

use Symfony\Component\Workflow\WorkflowInterface;

interface WorkflowConfig
{
    public static function make(): static;

    public function support(): string;

    public function instance(): WorkflowInterface;
}
