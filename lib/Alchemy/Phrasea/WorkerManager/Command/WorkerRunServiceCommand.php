<?php

namespace Alchemy\Phrasea\WorkerManager\Command;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\WorkerManager\Worker\Resolver\WorkerResolverInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerRunServiceCommand extends Command
{
    public function __construct()
    {
        parent::__construct('worker:run-service');

        $this->setDescription('Execute a service')
            ->addArgument('type', InputArgument::REQUIRED)
            ->addArgument('body', InputArgument::OPTIONAL)
            ->addOption('preserve-payload', 'p', InputOption::VALUE_NONE, 'Preserve temporary payload file');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var WorkerResolverInterface $workerResolver */
        $workerResolver = $this->container['alchemy_worker.type_based_worker_resolver'];

        $type = $input->getArgument('type');
        $body = $input->getArgument('body');


        $body = [];
        if($input->getArgument('body')) {
            $body = @file_get_contents($input->getArgument('body'));

            if ($body === false) {
                $output->writeln(sprintf('<error>Unable to read payload file %s</error>', $input->getArgument('body')));

                return;
            }

            $body = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $output->writeln('<error>Invalid message body</error>');

                return;
            }
        }

        $worker = $workerResolver->getWorker($type, $body);

        $worker->process($body);

        if (! $input->getOption('preserve-payload')) {
            @unlink($input->getArgument('body'));
        }

    }
}
