<?php

namespace Alchemy\Phrasea\WorkerManager\Command;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessageHandler;
use Alchemy\Phrasea\WorkerManager\Worker\WorkerInvoker;
use Doctrine\DBAL\Connection;
use PhpAmqpLib\Channel\AMQPChannel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerExecuteCommand extends Command
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('worker:execute');

        $this->setDescription('Listen queues define on configuration, launch corresponding service for execution')
            ->addOption('preserve-payload', 'p', InputOption::VALUE_NONE, 'Preserve temporary payload file')
            ->addOption('queue-name', '', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The name of queues to be consuming')
            ->addOption('max-processes', 'm', InputOption::VALUE_REQUIRED, 'The max number of process allow to run (default 1) ')
//            ->addOption('MWG', '', InputOption::VALUE_NONE, 'Enable MWG metadata compatibility (use only for write metadata service)')
//            ->addOption('clear-metadatas', '', InputOption::VALUE_NONE, 'Remove metadatas from documents if not compliant with Database structure (use only for write metadata service)')
            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        // close all connection initialized in databox and appbox class for this worker command
        //  the consumer will launch the command bin/console worker:run-service with all needed DB connection
        foreach ($this->container['dbs.options'] as $name => $options) {
            $this->container['dbs'][$name]->close();
        }
        $this->container['connection.pool.manager']->closeAll();
        // close DB connection finished

        $argQueueName = $input->getOption('queue-name');
        $maxProcesses = intval($input->getOption('max-processes'));

        /** @var AMQPConnection $serverConnection */
        $serverConnection = $this->container['alchemy_worker.amqp.connection'];

        /** @var AMQPChannel $channel */
        $channel = $serverConnection->getChannel();

        if ($channel == null) {
            $output->writeln("Can't connect to rabbit, check configuration!");

            return 1;
        }

        $serverConnection->declareExchange();

        /** @var WorkerInvoker $workerInvoker */
        $workerInvoker = $this->container['alchemy_worker.worker_invoker'];

        if ($input->getOption('max-processes') != null && $maxProcesses == 0) {
            $output->writeln('<error>Invalid max-processes option.Need an integer</error>');

            return 1;
        } elseif($maxProcesses) {
            $workerInvoker->setMaxProcessPoolValue($maxProcesses);
        }

        if ($input->getOption('preserve-payload')) {
            $workerInvoker->preservePayloads();
        }

        /** @var MessageHandler $messageHandler */
        $messageHandler = $this->container['alchemy_worker.message.handler'];
        $messageHandler->consume($channel, $serverConnection, $workerInvoker, $argQueueName, $maxProcesses);

        /** @var Connection $dbConnection */
        $dbConnection = $this->container['orm.em']->getConnection();

        while (count($channel->callbacks)) {
            // check connection for DB before given message to consumer
            // otherwise return 1
            if($dbConnection->ping() === false){
                $output->writeln("MySQL server is not available : retry to close and connect ....");

                try {
                    $dbConnection->close();
                    $dbConnection->connect();
                } catch (\Exception $e) {
                    // Mysql server can't be reconnected, so stop the worker
                    $serverConnection->connectionClose();

                    return 1;
                }
            }
            $channel->wait();
        }

        $serverConnection->connectionClose();

        return 0;
    }
}
