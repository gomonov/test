<?php

namespace App\UI\Command;

use App\Application\Event\Dto\EventInterface;
use App\Application\Event\EventGenerator;
use App\Infrastructure\Redis\Queue\Producer;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventGeneratorCommand extends Command
{
    private EventGenerator $eventGenerator;

    private Producer $producer;

    public function __construct(EventGenerator $eventGenerator, Producer $producer)
    {
        $this->eventGenerator = $eventGenerator;
        $this->producer = $producer;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('event:generator');
        $this->addArgument('count', InputArgument::OPTIONAL, 'Count event', 10000);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int)$input->getArgument('count');
        if ($count <= 0) {
            throw new InvalidArgumentException();
        }
        /** @var EventInterface $event */
        foreach ($this->eventGenerator->generate($count) as $event) {
            $output->writeln('Generate: <info>' . $event . '</info>');
            $this->producer->produce($event);
        }
        return 0;
    }
}