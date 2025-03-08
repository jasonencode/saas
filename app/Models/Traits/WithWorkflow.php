<?php

namespace App\Models\Traits;

use App\Extensions\Workflow\Workflow;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

trait WithWorkflow
{
    public function setWorkflowMarking(string $field, string $marking, array $context = []): void
    {
        $this->{$field} = $marking;
        $this->save();
    }

    public function apply(string $transition): Marking
    {
        $workflow = $this->getWorkflow();

        $definition = $workflow->getDefinition();
        $context = [];
        foreach ($definition->getTransitions() as $t) {
            if ($t->getName() == $transition) {
                $context = $workflow->getMetadataStore()->getTransitionMetadata($t);
            }
        }

        return $workflow->apply($this, $transition, $context);
    }

    public function getWorkflow(): WorkflowInterface
    {
        return resolve(Workflow::class)
            ->getWorkflow($this);
    }

    public function can(string $transition): bool
    {
        return $this->getWorkflow()
            ->can($this, $transition);
    }

    public function transitions(): array
    {
        return $this->getWorkflow()
            ->getEnabledTransitions($this);
    }
}
