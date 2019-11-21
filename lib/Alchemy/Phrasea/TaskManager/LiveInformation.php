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

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\Task as liveTask;

class LiveInformation
{
    private $status;
    private $notifier;

    public function __construct(TaskManagerStatus $status, NotifierInterface $notifier)
    {
        $this->status = $status;
        $this->notifier = $notifier;
    }

    /**
     * Returns live informations about the task manager.
     *
     * @return array
     */
    public function getManager($throwException = false)
    {
        $data = $this->query($throwException);

        return [
            'configuration' => $this->status->getStatus(),
            'actual'        => isset($data['manager']) ? TaskManagerStatus::STATUS_STARTED : TaskManagerStatus::STATUS_STOPPED,
            'process-id'    => isset($data['manager']) ? $data['manager']['process-id'] : null,
        ];
    }

    /**
     * Returns live informations about the given task.
     * @param liveTask  $task
     * @param boolean $throwException
     * @return array
     */
    public function getTask(liveTask $task, $throwException = false)
    {
        $data = $this->query($throwException);

        return $this->formatTask($task, $data);
    }

    /**
     * Returns live informations about some tasks.
     *
     * @param liveTask[]  $tasks
     * @param boolean $throwException
     *
     * @return array
     */
    public function getTasks($tasks, $throwException = false)
    {
        $data = $this->query($throwException);

        $ret = [];
        foreach ($tasks as $task) {
            $ret[$task->getId()] = $this->formatTask($task, $data);
        }

        return $ret;
    }

    private function formatTask(liveTask $task, $data)
    {
        $taskData = (isset($data['jobs']) && isset($data['jobs'][$task->getId()])) ? $data['jobs'][$task->getId()] : [];

        return [
            'configuration' => $task->getStatus(),
            'actual'        => isset($taskData['status']) ? $taskData['status'] : liveTask::STATUS_STOPPED,
            'process-id'    => isset($taskData['process-id']) ? $taskData['process-id'] : null,
        ];
    }

    private function query($throwException)
    {
        try {
            return $this->notifier->notify(NotifierInterface::MESSAGE_INFORMATION);
        } catch (RuntimeException $e) {
            if ($throwException) {
                throw $e;
            }

            return [];
        }
    }
}
