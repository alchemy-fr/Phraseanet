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

class TaskStop extends Command
{
    public function __construct()
    {
        parent::__construct('task-manager:task:stop');

        $this
            ->addArgument('task_id', InputArgument::REQUIRED, 'The task_id to stop')
            ->setDescription('Stops a task');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->container['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $task = $this->container['converter.task']->convert($input->getArgument('task_id'));
        $this->container['manipulator.task']->stop($task);

        return 0;
    }
}
