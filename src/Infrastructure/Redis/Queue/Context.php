<?php

namespace App\Infrastructure\Redis\Queue;

interface Context
{
    public function isStop(): bool;

    public function writeln(string $message): void;
}