<?php

/*
 * This file is part of alchemy/worker-bundle.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\WorkerBundle\Commands;

use Alchemy\Worker\MessageDispatcher;
use Alchemy\Worker\WorkerInvoker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DispatchingConsumerCommand extends Command
{
    /**
     * @var MessageDispatcher
     */
    private $messageDispatcher;

    /**
     * @var WorkerInvoker
     */
    private $workerInvoker;

    /**
     * @param MessageDispatcher $messageDispatcher
     * @param WorkerInvoker $workerInvoker
     */
    public function __construct(MessageDispatcher $messageDispatcher, WorkerInvoker $workerInvoker)
    {
        parent::__construct();

        $this->messageDispatcher = $messageDispatcher;
        $this->workerInvoker = $workerInvoker;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName('workers:run-dispatcher')
            ->addOption('preserve-payload', 'p', InputOption::VALUE_NONE);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('preserve-payload')) {
            $this->workerInvoker->preservePayloads();
        }

        while (true) {
            $this->messageDispatcher->run();
        }
    }
}
