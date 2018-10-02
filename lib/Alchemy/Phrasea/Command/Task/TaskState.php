<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TaskState extends Command
{
    public function __construct()
    {
        parent::__construct('task-manager:task:state');

        $this
            ->addArgument('task_id', InputArgument::REQUIRED, 'The task_id to test')
            ->setDescription('Returns the state of a task')
            ->addOption('short', null, InputOption::VALUE_NONE, 'print short result, ie: <info>stopped()</info> | <info>started(12345)</info> | <info>tostop(12345)</info> | <info>...</info>');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->container['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $task_id = $input->getArgument('task_id');
        if (null === $task = $this->container['repo.tasks']->find($task_id)) {
            throw new RuntimeException('Invalid task_id');
        }

        $info = $this->container['task-manager.live-information']->getTask($task);
        $error = $info['configuration'] !== $info['actual'];
        $actual = $error ? "<error>" .$info['actual']. "</error>" : "<info>".$info['actual']."</info>";
        $configuration = $error ? "<comment>".$info['configuration']."</comment>" : "<info>".$info['configuration']."</info>";

        if (null === $info['process-id']) {
            if ($input->getOption('short')) {
                $output->writeln(sprintf('%s', $actual));
            } else {
                $output->writeln(sprintf('Task is %s (configured with `%s`)', $actual, $configuration));
            }
        } else {
            if ($input->getOption('short')) {
                $output->writeln(sprintf('%s(%s)', $actual, $info['process-id']));
            } else {
                $output->writeln(sprintf('Task is %s (configured with `%s`) with process-id %d', $actual, $configuration, $info['process-id']));
            }
        }

        return (int) $error;
    }
}
