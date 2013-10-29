<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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

class TaskStart extends Command
{
    public function __construct()
    {
        parent::__construct('task-manager:task:start');

        $this
            ->addArgument('task_id', InputArgument::REQUIRED, 'The task_id to start')
            ->setDescription('Starts a task');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $task = $this->container['converter.task']->convert($input->getArgument('task_id'));
        $this->container['manipulator.task']->start($task);

        return 0;
    }
}
