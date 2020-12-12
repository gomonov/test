<?php

namespace App\UI\Service\Daemon;

use App\Infrastructure\Redis\Queue\Context;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DaemonContext implements Context
{

    private InputInterface $input;

    private OutputInterface $output;

    private int $pause;

    private int $memoryLimit;

    private ?int $timeLimit;

    private ?int $iterationsLimit;

    private float $timeStart;

    private int $iterations = 0;

    private bool $isStop = false;

    /**
     * DaemonContext constructor.
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param int             $pause
     * @param int             $memoryLimit
     * @param int|null        $timeLimit
     * @param int|null        $iterationsLimit
     */
    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        int $pause,
        int $memoryLimit,
        ?int $timeLimit,
        ?int $iterationsLimit
    ) {
        $this->input           = $input;
        $this->output          = $output;
        $this->pause           = $pause;
        $this->memoryLimit     = $memoryLimit;
        $this->timeLimit       = $timeLimit;
        $this->iterationsLimit = $iterationsLimit;
        $this->timeStart       = microtime(true);
    }

    /**
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function writeln(string $message): void
    {
        $this->getOutput()->writeln($message);
    }

    /**
     * @return float
     */
    public function getExecutionTime(): float
    {
        return round(microtime(true) - $this->timeStart, 3);
    }

    public function incrementIterations(): void
    {
        $this->iterations++;
    }

    /**
     * @return int
     */
    public function getIterationsCount(): int
    {
        return $this->iterations;
    }

    /**
     * @return int|null
     */
    public function getLimitIterations(): ?int
    {
        return $this->iterationsLimit;
    }

    /**
     * @return int
     */
    public function getMemoryLimit(): int
    {
        return $this->memoryLimit;
    }

    /**
     * @return int|null
     */
    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function stop(): void
    {
        $this->isStop = true;
    }

    /**
     * @return bool
     */
    public function isStop(): bool
    {
        return $this->isStop;
    }

    /**
     * @return int
     */
    public function getPauseBetweenIterations(): int
    {
        return $this->pause;
    }

}