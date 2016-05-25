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
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\TaskManager\Event\FinishedJobRemoverSubscriber;
use Alchemy\Phrasea\TaskManager\Job\AbstractJob;
use Alchemy\Phrasea\TaskManager\Job\JobData;
use Alchemy\TaskManager\Event\JobSubscriber\DurationLimitSubscriber;
use Alchemy\TaskManager\Event\JobSubscriber\LockFileSubscriber;
use Alchemy\TaskManager\Event\JobSubscriber\MemoryLimitSubscriber;
use Alchemy\TaskManager\Event\JobSubscriber\SignalControlledSubscriber;
use Alchemy\TaskManager\Event\JobSubscriber\StopSignalSubscriber;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TaskRun extends TaskManagerCommand
{
    public function __construct()
    {
        parent::__construct('task-manager:task:run');

        $this
            ->setDescription('Runs a task')
            ->addArgument('task_id', InputArgument::REQUIRED, 'The id of the task to run', null)
            ->addOption('max-memory', null, InputOption::VALUE_REQUIRED, '')
            ->addOption('max-duration', null, InputOption::VALUE_REQUIRED, '')
            ->addOption('listen-signal', null, InputOption::VALUE_NONE, '')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->assertTaskManagerIsEnabled();

        declare(ticks=1000);

        $task = $this->getTask($input);

        $this->configureLogger(function () use ($task) {
            return $this->getTaskManagerLogFileFactory()->forTask($task);
        });

        $job = $this->createJob($input, $task, $this->getTaskManagerLogger());

        $job->run(new JobData($this->container, $task));
    }

    /**
     * @param InputInterface $input
     * @return Task
     */
    protected function getTask(InputInterface $input)
    {
        if (null === $task = $this->container['repo.tasks']->find($input->getArgument('task_id'))) {
            throw new RuntimeException('Invalid task_id');
        }

        return $task;
    }

    /**
     * @param InputInterface $input
     * @param Task $task
     * @param Logger $logger
     * @return AbstractJob
     */
    protected function createJob(InputInterface $input, Task $task, Logger $logger)
    {
        $job = $this->container['task-manager.job-factory']->create($task->getJobId());

        $job->addSubscriber(new LockFileSubscriber('task-' . $task->getId(), $logger, $this->container['tmp.path'] . '/locks'));
        $job->addSubscriber(new StopSignalSubscriber($this->getSignalHandler(), $logger));

        if ($input->getOption('listen-signal')) {
            $job->addSubscriber(new SignalControlledSubscriber($this->getSignalHandler(), 2, $logger));
        }
        if (null !== $maxDuration = $input->getOption('max-duration')) {
            $job->addSubscriber(new DurationLimitSubscriber($maxDuration, $logger));
        }
        if (null !== $maxMemory = $input->getOption('max-memory')) {
            $job->addSubscriber(new MemoryLimitSubscriber($maxMemory, $logger));
        }
        if ($task->isSingleRun()) {
            $job->addSubscriber(new FinishedJobRemoverSubscriber($this->container['orm.em']));
        }

        return $job;
    }
}
