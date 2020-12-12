<?php

namespace App\Infrastructure\Redis\Queue;

use App\Application\Event\EventExecutor;
use App\Application\Redis\RedisStorageInterface;

class Consumer
{
    private RedisStorageInterface $redisStorage;

    private EventExecutor $eventExecutor;

    public function __construct(RedisStorageInterface $redisStorage, EventExecutor $eventExecutor)
    {
        $this->redisStorage  = $redisStorage;
        $this->eventExecutor = $eventExecutor;
    }

    public function consume(Context $context)
    {
        $queueName = $this->getQueue($context);
        if (empty($queueName)) {
            return;
        }

        while (!$context->isStop()) {
            $message = $this->redisStorage->lIndex($queueName, -1);
            if (!$message) {
                break;
            }
            $event = unserialize($message);

            $context->getOutput()->writeln('Execute: <info>' . $event . '</info>');
            if ($this->eventExecutor->execute($event)) {
                $this->redisStorage->lTrim($queueName, 0, -2);
            }
            $this->refreshQueueBlock($queueName);
        }

        $this->releaseQueue($queueName);
    }

    private function getQueue(Context $context): ?string
    {
        $queueName = null;
        while (!$context->isStop()) {
            $queues = $this->redisStorage->keys(Queue::QUEUE_NAME_PREFIX . '*');
            $blockedQueues = $this->redisStorage->keys(Queue::QUEUE_BLOCK_NAME_PREFIX . '*');
            $queues = array_diff($queues, $blockedQueues);
            if (empty($queues)) {
                usleep(10000);
                continue;
            }
            $queueName = $queues[array_rand($queues)];
            $value = $this->redisStorage->incrBy(Queue::QUEUE_BLOCK_NAME_PREFIX . $queueName, 1, 30);
            if ($value == 1) {
                break;
            }
            usleep(10000);
        }
        return $queueName;
    }

    private function releaseQueue(string $queueName)
    {
        $this->redisStorage->del(Queue::QUEUE_BLOCK_NAME_PREFIX . $queueName);
    }

    private function refreshQueueBlock(string $queueName)
    {
        $this->redisStorage->expire(Queue::QUEUE_BLOCK_NAME_PREFIX . $queueName, 30);
    }
}