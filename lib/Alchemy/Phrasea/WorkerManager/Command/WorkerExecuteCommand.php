<?php

namespace Alchemy\Phrasea\WorkerManager\Command;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessageHandler;
use Alchemy\Phrasea\WorkerManager\Worker\WorkerInvoker;
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
            ->addOption('max-processes', 'm', InputOption::VALUE_REQUIRED, 'The max number of process allow to run (default 4) ')
            ->addOption('MWG', '', InputOption::VALUE_NONE, 'Enable MWG metadata compatibility (use only for write metadata service)')
            ->addOption('clear-metadatas', '', InputOption::VALUE_NONE, 'Delete metadatas from documents if not compliant with Database structure (use only for write metadata service)')
            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $MWG            = false;
        $clearMetadatas = false;

        $argQueueName = $input->getOption('queue-name');
        $maxProcesses = intval($input->getOption('max-processes'));

        /** @var AMQPConnection $serverConnection */
        $serverConnection = $this->container['alchemy_worker.amqp.connection'];

        /** @var AMQPChannel $channel */
        $channel = $serverConnection->getChannel();

        if ($channel == null) {
            $output->writeln("Can't connect to rabbit, check configuration!");

            return;
        }

        $serverConnection->declareExchange();

        /** @var WorkerInvoker $workerInvoker */
        $workerInvoker = $this->container['alchemy_worker.worker_invoker'];

        if ($input->getOption('max-processes') != null && $maxProcesses == 0) {
            $output->writeln('<error>Invalid max-processes option.Need an integer</error>');

            return;
        } elseif($maxProcesses) {
            $workerInvoker->setMaxProcessPoolValue($maxProcesses);
        }

        if ($input->getOption('MWG')) {
            $MWG = true;
        }

        if ($input->getOption('clear-metadatas')) {
            $clearMetadatas = true;
        }

        if ($input->getOption('preserve-payload')) {
            $workerInvoker->preservePayloads();
        }

        /** @var MessageHandler $messageHandler */
        $messageHandler = $this->container['alchemy_worker.message.handler'];
        $messageHandler->consume($serverConnection, $workerInvoker, $argQueueName, $maxProcesses);

        while (count($channel->callbacks)) {
            $output->writeln("[*] Waiting for messages. To exit press CTRL+C");
            $channel->wait();
        }

        $serverConnection->connectionClose();
    }

}
