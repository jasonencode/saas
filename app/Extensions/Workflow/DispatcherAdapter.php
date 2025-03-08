<?php

namespace App\Extensions\Workflow;

use App\Extensions\Workflow\Events\WorkflowEvent;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DispatcherAdapter implements EventDispatcherInterface
{
    private const EVENT_MAP = [
//        'guard' => GuardEvent::class,
//        'leave' => LeaveEvent::class,
//        'transition' => TransitionEvent::class,
//        'enter' => EnterEvent::class,
//        'entered' => EnteredEvent::class,
//        'completed' => CompletedEvent::class,
//        'announce' => AnnounceEvent::class,
    ];

    protected Dispatcher $dispatcher;

    private array $plainEvents;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->plainEvents = array_map(function($event) {
            return "workflow.$event";
        }, array_keys(static::EVENT_MAP));
    }

    public function dispatch(object $event, string $eventName = null): object
    {
        $name = is_null($eventName) ? get_class($event) : $eventName;

        $eventToDispatch = $this->translateEvent($eventName, $event);

        if ($this->shouldDispatchPlainClassEvent($eventName)) {
            $this->dispatcher->dispatch($eventToDispatch);
        }

        $this->dispatcher->dispatch($name, $eventToDispatch);

        return $eventToDispatch;
    }

    private function translateEvent(?string $eventName, object $symfonyEvent): object
    {
        if (is_null($eventName)) {
            return WorkflowEvent::newFromBase($symfonyEvent);
        }

        $event = $this->parseWorkflowEventFromEventName($eventName);

        if (!$event) {
            return WorkflowEvent::newFromBase($symfonyEvent);
        }

        $translatedEventClass = static::EVENT_MAP[$event];

        return $translatedEventClass::newFromBase($symfonyEvent);
    }

    private function parseWorkflowEventFromEventName(string $eventName): false|string
    {
        $eventSearch = preg_match('/\.(?P<event>'.implode('|', array_keys(static::EVENT_MAP)).')(\.|$)/i', $eventName,
            $eventMatches);

        if (!$eventSearch) {
            // no results or error
            return false;
        }
        $event = $eventMatches['event'] ?? false;

        if (!array_key_exists($event, static::EVENT_MAP)) {
            return false;
        }

        return $event;
    }

    private function shouldDispatchPlainClassEvent(?string $eventName = null): bool
    {
        if (!$eventName) {
            return false;
        }

        return in_array($eventName, $this->plainEvents);
    }
}