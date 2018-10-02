<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager;

use Alchemy\Phrasea\Model\Entities\Task as TaskEntity;
use Alchemy\Phrasea\Model\Repositories\TaskRepository;
use Alchemy\TaskManager\TaskListInterface;
use Symfony\Component\Process\ProcessBuilder;

class TaskList implements TaskListInterface
{
    private $repository;
    private $root;
    private $phpConf;
    private $phpExec;

    public function __construct(TaskRepository $repository, $root, $phpExec, $phpConf = null)
    {
        $this->repository = $repository;
        $this->root = $root;
        $this->phpConf = $phpConf;
        $this->phpExec = $phpExec;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh()
    {
        return array_map([$this, 'entityToTask'], $this->repository->findActiveTasks());
    }

    public function entityToTask(TaskEntity $task)
    {
        $name = $task->getId() ;
        $arguments = ['exec', $this->phpExec];

        if ($this->phpConf) {
            $arguments[] = '-c';
            $arguments[] = $this->phpConf;
        }

        $maxmegs     = 128;     // default (Mo) if not set in xml
        $maxduration = 1800;    // default (seconds) if not set in xml
        if( ($sxSettings = @simplexml_load_string($task->getSettings())) ) {
            if( ($v = (int)($sxSettings->maxmegs)) && $v > 0) {
                $maxmegs = $v;
            }
            if( ($v = (int)($sxSettings->maxduration)) && $v > 0) {
                $maxduration = $v;
            }
        }

        $arguments[] = '-f';
        $arguments[] = $this->root . '/bin/console';
        $arguments[] = '--';
        $arguments[] = '-q';
        $arguments[] = 'task-manager:task:run';
        $arguments[] = $task->getId();
        $arguments[] = '--listen-signal';
        $arguments[] = '--max-duration';
        $arguments[] = $maxduration;
        $arguments[] = '--max-memory';
        $arguments[] = $maxmegs << 20;

        $builder = ProcessBuilder::create($arguments);
        $builder->setTimeout(0);

        return new Task($task, $name, $task->isSingleRun() ? 1 : INF, $builder->getProcess());
    }
}
