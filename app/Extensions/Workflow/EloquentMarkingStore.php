<?php

namespace App\Extensions\Workflow;

use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

class EloquentMarkingStore implements MarkingStoreInterface
{
    public function __construct(private string $field = 'marking')
    {
    }

    public function getMarking(object $subject): Marking
    {
        $marking = $subject->{$this->field};

        if (null === $marking) {
            return new Marking();
        }

        if (is_string($marking)) {
            $marking = [$marking => 1];
        } else {
            $marking = [(string) $marking->value => 1];
        }

        return new Marking($marking);
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        $places = $marking->getPlaces();
        $toPlace = key($places);

        $method = 'setWorkflowMarking';
        if (method_exists($subject, $method)) {
            $subject->{$method}($this->field, $toPlace, $context);
        }
    }
}
