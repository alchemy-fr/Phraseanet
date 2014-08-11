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

use Alchemy\TaskManager\TaskManager;
use Alchemy\Phrasea\Command\Command;
use Alchemy\TaskManager\Event\TaskManagerSubscriber\LockFileSubscriber;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerRun extends Command
{
    public function __construct()
    {
        parent::__construct('task-manager:scheduler:run');
        $this->setDescription('Run the scheduler');
    }

    public function signalHandler($signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                $this->container['signal-handler']->unregisterAll();
                $this->container['task-manager']->stop();
                break;
        }
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->container['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        declare(ticks=1);

        if ($this->container['task-manager.logger.configuration']['enabled']) {
            $file = $this->container['task-manager.log-file.factory']->forManager();
            $this->container['task-manager.logger']->pushHandler(new RotatingFileHandler($file->getPath(), $this->container['task-manager.logger.configuration']['max-files'], $this->container['task-manager.logger.configuration']['level']));
        }

        $this->container['signal-handler']->register([SIGINT, SIGTERM], [$this, 'signalHandler']);
        $this->container['task-manager']->addSubscriber(new LockFileSubscriber($this->container['task-manager.logger'], $this->container['root.path'].'/tmp/locks'));
        $this->container['task-manager']->start();
    }
}
