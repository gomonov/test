<?php

namespace App\Infrastructure\Redis\Queue;

use App\Application\Event\Dto\EventInterface;
use App\Application\Redis\RedisStorageInterface;

class Producer
{
    private RedisStorageInterface $redisStorage;

    public function __construct(RedisStorageInterface $redisStorage)
    {
        $this->redisStorage = $redisStorage;
    }

    public function produce(EventInterface $event)
    {
        $this->redisStorage->rPush(Queue::QUEUE_NAME_PREFIX . $event->getUserId(), serialize($event));
    }
}