<?php

namespace App\Application\Event;

use App\Application\Event\Dto\Event;
use Generator;

class EventGenerator
{
    public function generate(int $count): Generator
    {
        $currentEvent = 1;
        while (true) {
            $userId   = rand(0, 1000);
            $size     = rand(1, 10);
            $eventIds = range($currentEvent, $currentEvent + $size);
            foreach ($eventIds as $eventId) {
                yield new Event($userId, hrtime(true));
                if ($eventId == $count) {
                    return;
                }
            }
            $currentEvent += ($size + 1);
        }
    }
}