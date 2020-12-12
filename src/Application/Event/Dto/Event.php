<?php

namespace App\Application\Event\Dto;

class Event implements EventInterface
{
    private int $userId;

    private float $eventId;

    public function __construct(int $userId, float $eventId)
    {
        $this->userId  = $userId;
        $this->eventId = $eventId;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    public function __toString(): string
    {
        return 'user: ' . $this->userId . ' - event: ' . $this->eventId;
    }

}