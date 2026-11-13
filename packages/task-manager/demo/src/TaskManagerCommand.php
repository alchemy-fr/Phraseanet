<?php

namespace Alchemy\TaskManager\Demo;

use Alchemy\TaskManager\TaskManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Neutron\SignalHandler\SignalHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TaskManagerCommand extends Command
{
    public function __construct()
    {
        parent::__construct('console:run-task-manager');

        $this->addOption('host', null, InputOption::VALUE_REQUIRED, 'The host to bind to.', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'The port tot bind to.', '6660');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        declare(ticks=1);

        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $manager = TaskManager::create(new EventDispatcher(), $logger, new TaskList(), array(
            'listener_host' => $input->getOption('host'),
            'listener_port' => $input->getOption('port'),
        ));

        $handler = SignalHandler::getInstance();
        $handler->register(array(SIGINT, SIGTERM), function ($signal) use ($manager) {
            $manager->stop();
        });

        $manager->start();

        return Command::SUCCESS;
    }
}
