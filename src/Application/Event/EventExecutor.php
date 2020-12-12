<?php

namespace App\Application\Event;

use App\Application\Event\Dto\Event;
use Psr\Log\LoggerInterface;

class EventExecutor
{
    private LoggerInterface $logger;

    private bool $noError;

    public function __construct(LoggerInterface $executorLogger, bool $noError)
    {
        $this->logger  = $executorLogger;
        $this->noError = $noError;
    }

    /**
     * @param Event $event
     * @return bool
     */
    public function execute(Event $event): bool
    {
        sleep(1);
        if ($this->noError) {
            $this->logger->info($event);
            return true;
        } else {
            $result = (boolean)rand(0, 1);
            if ($result) {
                $this->logger->info($event);
            } else {
                $this->logger->warning($event);
            }
            return $result;
        }
    }
}