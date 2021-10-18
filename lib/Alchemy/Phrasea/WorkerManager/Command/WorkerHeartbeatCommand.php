<?php

namespace Alchemy\Phrasea\WorkerManager\Command;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\HeartbeatHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerHeartbeatCommand extends Command
{
    const DEFAULT_INTERVAL = 30;

    public function __construct()
    {
        parent::__construct('worker:heartbeat');

        $this
            ->setDescription('Heartbeat connection to track drops or broken pipes')
            ->addOption('heartbeat', null, InputOption::VALUE_REQUIRED, sprintf('in seconds (default: %d)', self::DEFAULT_INTERVAL))
        ;

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var AMQPConnection $serverConnection */
        $serverConnection = $this->container['alchemy_worker.amqp.connection'];

        $connection = $serverConnection->getConnection();

        $interval = $input->getOption('heartbeat');
        if (empty($interval)) {
            $interval = self::DEFAULT_INTERVAL;
        }

        $heartbeatHandler = new HeartbeatHandler($connection);
        $heartbeatHandler->run($interval);

        return 0;
    }
}
