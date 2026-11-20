<?php

namespace Alchemy\TaskManager\Demo;

use Neutron\SignalHandler\SignalHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunJobCommand extends Command
{
    public function __construct()
    {
        parent::__construct('console:run-task');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $job = new Job();

        $handler = SignalHandler::getInstance();
        $handler->register(array(SIGINT, SIGTERM), function ($signal) use ($job) {
            $job->stop();
        });

        $job->run();

        return Command::SUCCESS;
    }
}
