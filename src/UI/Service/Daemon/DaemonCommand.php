<?php

namespace App\UI\Service\Daemon;

use DateTime;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


abstract class DaemonCommand extends Command
{

    public function __construct()
    {
        parent::__construct();
        $this->addOption('pause', null, InputOption::VALUE_OPTIONAL, 'Pause between iterations in seconds', 0);
        $this->addOption('memory', null, InputOption::VALUE_OPTIONAL, 'Memory limit in megabytes', -1);
        $this->addOption('time', null, InputOption::VALUE_OPTIONAL, 'Time limit in seconds');
        $this->addOption('iterations', null, InputOption::VALUE_OPTIONAL, 'Iterations limit');
    }


    protected function stop(DaemonContext $context): void
    {
        $context->stop();
    }

    abstract protected function executeIteration(DaemonContext $context): void;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('[' . (new DateTime())->format('Y-m-d H:i:s') . '] <info>Start command ' . $this->getName() . '</info>');
        $context = $this->buildContext($input, $output);
        $this->registerHandlers($context);

        while (!$context->isStop()) {
            $this->executeIteration($context);
            $context->incrementIterations();
            $this->info($context, OutputInterface::VERBOSITY_VERBOSE);
            if (!$this->checkStop($context)) {
                $this->pause($context);
            }
        }

        $this->info($context);
        $output->writeln('[' . (new DateTime())->format('Y-m-d H:i:s') . '] <info>Terminate process ' . $this->getName() . '</info>');
        return 0;
    }

    private function info(DaemonContext $context, int $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $context->getOutput()->writeln(
            '[' . (new DateTime())->format(
                'Y-m-d H:i:s'
            ) . '] <info>Total execution time:</info> ' . $context->getExecutionTime() . 's',
            $verbosity
        );
        $context->getOutput()->writeln(
            '[' . (new DateTime())->format('Y-m-d H:i:s') . '] <info>Memory usage:</info> ' . round(
                memory_get_usage(true) / 1024 / 1024,
                3
            ) . 'MB',
            $verbosity
        );
        $context->getOutput()->writeln(
            '[' . (new DateTime())->format('Y-m-d H:i:s') . '] <info>Memory peak usage:</info> ' . round(
                memory_get_peak_usage(true) / 1024 / 1024,
                3
            ) . 'MB',
            $verbosity
        );
        $context->getOutput()->writeln(
            '[' . (new DateTime())->format(
                'Y-m-d H:i:s'
            ) . '] <info>Iterations count:</info> ' . $context->getIterationsCount(),
            $verbosity
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return DaemonContext
     */
    private function buildContext(InputInterface $input, OutputInterface $output): DaemonContext
    {
        $pause = (int)$input->getOption('pause');
        if ($pause < 0) {
            throw new InvalidArgumentException('Pause time option value should be greater than 0');
        }
        $memoryLimit = (int)$input->getOption('memory');
        $memoryLimit = $memoryLimit === -1 ? $memoryLimit : $memoryLimit * 1024 * 1024;
        if ($memoryLimit === 0 || $memoryLimit < -1) {
            throw new InvalidArgumentException('Memory limit option value should be greater than 0 or equal to -1');
        }
        if ($memoryLimit > memory_limit_bytes()) {
            throw new InvalidArgumentException(
                'Memory limit option value should not be greater than "memory_limit" in the php.ini (' . ini_get(
                    'memory_limit'
                ) . ')'
            );
        }

        $timeLimit = $input->getOption('time');
        $timeLimit = $timeLimit === null ? $timeLimit : (int)$timeLimit;
        if ($timeLimit !== null && $timeLimit <= 0) {
            throw new InvalidArgumentException('Time limit option value should be greater than 0');
        }

        $iterationsLimit = $input->getOption('iterations');
        $iterationsLimit = $iterationsLimit === null ? $iterationsLimit : (int)$iterationsLimit;
        if ($iterationsLimit !== null && $iterationsLimit <= 0) {
            throw new InvalidArgumentException('Iterations limit option value should be greater than 0');
        }

        return new DaemonContext(
            $input,
            $output,
            $pause,
            $memoryLimit,
            $timeLimit,
            $iterationsLimit
        );
    }

    /**
     * @param DaemonContext $context
     */
    private function registerHandlers(DaemonContext $context): void
    {
        pcntl_async_signals(true);

        foreach ([SIGTERM, SIGINT] as $signal) {
            pcntl_signal($signal, [$context, 'stop']);
        }
    }

    /**
     * @param DaemonContext $context
     */
    private function pause(DaemonContext $context): void
    {
        if ($context->getPauseBetweenIterations() < 0) {
            throw new InvalidArgumentException('Pause time argument value should be greater than 0');
        }
        if ($context->getPauseBetweenIterations() === 0) {
            return;
        }
        sleep($context->getPauseBetweenIterations());
    }

    /**
     * @param DaemonContext $context
     * @return bool
     */
    private function checkStop(DaemonContext $context): bool
    {
        if ($context->getLimitIterations() !== null && $context->getIterationsCount() >= $context->getLimitIterations(
            )) {
            $context->getOutput()->writeln(
                '[' . (new DateTime())->format('Y-m-d H:i:s') . '] <info>Reached the maximum number of iterations</info>'
            );
            $context->stop();
            return true;
        }
        if ($context->getTimeLimit() !== null && $context->getExecutionTime() >= $context->getTimeLimit()) {
            $context->getOutput()->writeln('[' . (new DateTime())->format('Y-m-d H:i:s') . '] <info>Reached the time limit</info>');
            $context->stop();
            return true;
        }
        if ($context->getMemoryLimit() !== -1 && memory_get_usage(true) >= $context->getMemoryLimit()) {
            $context->getOutput()->writeln(
                '[' . (new DateTime())->format('Y-m-d H:i:s') . '] <info>Reached the memory limit</info>'
            );
            $context->stop();
            return true;
        }
        return false;
    }

}