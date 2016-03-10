<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\TaskManagerCommand;
use Alchemy\TaskManager\Event\TaskManagerSubscriber\LockFileSubscriber;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerRun extends TaskManagerCommand
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
                $this->getSignalHandler()->unregisterAll();
                $this->getTaskManager()->stop();
                break;
        }
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->assertTaskManagerIsEnabled();
        $this->configureLogger(function () {
            return $this->getTaskManagerLogFileFactory()->forManager();
        });

        declare(ticks=1000);

        $this->getSignalHandler()->register([SIGINT, SIGTERM], [$this, 'signalHandler']);
        $this->getTaskManager()->addSubscriber(new LockFileSubscriber($this->getTaskManagerLogger(), $this->container['tmp.path'].'/locks'));
        $this->getTaskManager()->start();

    }
}
