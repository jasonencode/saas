<?php

namespace App\Extensions\Workflow\Events;

use Symfony\Component\Workflow\Event\Event;

abstract class BaseEvent extends Event
{
    /**
     * Creates a new instance from the base Symfony event
     */
    public static function newFromBase(Event $symfonyEvent): static
    {
        return new static(
            $symfonyEvent->getSubject(),
            $symfonyEvent->getMarking(),
            $symfonyEvent->getTransition(),
            $symfonyEvent->getWorkflow()
        );
    }

    public function __serialize(): array
    {
        return [
            'base_event_class' => get_class($this),
            'subject' => $this->getSubject(),
            'marking' => $this->getMarking(),
            'transition' => $this->getTransition(),
            'workflow' => [
                'name' => $this->getWorkflowName(),
            ],
        ];
    }

    public function __unserialize(array $data): void
    {
        $workflowName = $data['workflow']['name'] ?? null;
        parent::__construct(
            $data['subject'],
            $data['marking'],
            $data['transition'],
            app('workflow')->get($data['subject'], $workflowName)
        );
    }
}