<?php

namespace App\UI\Command;

use App\Infrastructure\Redis\Queue\Consumer;
use App\UI\Service\Daemon\DaemonCommand;
use App\UI\Service\Daemon\DaemonContext;

class EventExecutorCommand extends DaemonCommand
{
    private Consumer $consumer;

    /**
     * EventExecutorCommand constructor.
     * @param Consumer $consumer
     */
    public function __construct(Consumer $consumer)
    {
        parent::__construct();
        $this->consumer = $consumer;
    }

    protected function configure()
    {
        $this->setName('event:executor');
    }

    protected function executeIteration(DaemonContext $context): void
    {
        $this->consumer->consume($context);
    }
}