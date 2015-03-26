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
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerResumeTasks extends Command
{
    public function __construct()
    {
        parent::__construct('task-manager:scheduler:resume-tasks');
        $this->setDescription('Resume scheduler started tasks jobs');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->container['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $ret = 0;

        $this->container['task-manager.status']->start();
        $output->writeln("Task manager configuration has been toggled on start.");

        $info = $this->container['task-manager.live-information']->getManager();
        if (TaskManagerStatus::STATUS_STARTED !== $info['actual']) {
            $output->writeln(sprintf('Task manager is currently <error>%s</error>, please consider start it.', $info['actual']));
            $ret = 1;
        } else {
            $output->writeln('Task manager is currently <info>running</info>.');
        }

        return $ret;
    }
}
