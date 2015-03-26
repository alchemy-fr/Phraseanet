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

class SchedulerPauseTasks extends Command
{
    public function __construct()
    {
        parent::__construct('task-manager:scheduler:pause-tasks');
        $this->setDescription('Pause scheduler started tasks jobs');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->container['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $ret = 0;

        $this->container['task-manager.status']->stop();
        $output->writeln("Task manager configuration has been toggled on stop");

        $info = $this->container['task-manager.live-information']->getManager();
        if (TaskManagerStatus::STATUS_STARTED !== $info['actual']) {
            $output->writeln(sprintf('Be careful, task manager status is <comment>%s</comment>.', $info['actual']));
            $ret = 1;
        } else {
            $output->writeln('Task manager is currently <info>running</info>, all tasks are currently paused as of this operation.');
        }

        return $ret;
    }
}
