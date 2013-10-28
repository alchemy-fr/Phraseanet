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

use Alchemy\TaskManager\TaskManager;
use Alchemy\Phrasea\Command\Command;
use Alchemy\TaskManager\Event\TaskManagerSubscriber\LockFileSubscriber;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskManagerCommand extends Command
{
    public function __construct()
    {
        parent::__construct('task-manager:scheduler:run');
    }

    public function signalHandler($signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                $this->container['task-manager']->stop();
                break;
        }
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        declare(ticks=1);
        $this->container['signal-handler']->register(array(SIGINT, SIGTERM), array($this, 'signalHandler'));
        $this->container['task-manager']->addSubscriber(new LockFileSubscriber($this->container['task-manager.logger'], $this->container['root.path'].'/tmp/locks'));
        $this->container['task-manager']->start();
    }
}
