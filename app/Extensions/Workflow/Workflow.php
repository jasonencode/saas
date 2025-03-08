<?php

namespace App\Extensions\Workflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * 工作流程工厂类
 *
 * @package App\Factories\Workflow
 */
class Workflow
{
    protected Registry $registry;

    protected EventDispatcherInterface $dispatcher;

    public function __construct()
    {
        $this->registry = new Registry();
        $this->dispatcher = new DispatcherAdapter(app(Dispatcher::class));
    }

    public function getWorkflow(Model $subject, string $workflowName = null): WorkflowInterface
    {
        return $this->registry->get($subject, $workflowName);
    }

    public function addWorkflow(WorkflowConfig $config): void
    {
        $this->registry->addWorkflow($config->instance(), new InstanceOfSupportStrategy($config->support()));
    }
}
