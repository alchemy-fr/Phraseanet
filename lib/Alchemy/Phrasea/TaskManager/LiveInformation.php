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

use Alchemy\Phrasea\TaskManager\Notifier;
use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Alchemy\Phrasea\Model\Entities\Task;

class LiveInformation
{
    private $status;
    private $notifier;

    public function __construct(TaskManagerStatus $status, Notifier $notifier)
    {
        $this->status = $status;
        $this->notifier = $notifier;
    }

    /**
     * Returns live informations about the task manager.
     *
     * @return array
     */
    public function getManager()
    {
        $data = $this->notifier->notify(Notifier::MESSAGE_INFORMATIONS);

        return [
            'configuration' => $this->status->getStatus(),
            'actual'        => isset($data['manager']) ? TaskManagerStatus::STATUS_STARTED : TaskManagerStatus::STATUS_STOPPED,
            'process-id'    => isset($data['manager']) ? $data['manager']['process-id'] : null,
        ];
    }

    /**
     * Returns live informations about the given task.
     *
     * @return array
     */
    public function getTask(Task $task)
    {
        $data = $this->notifier->notify(Notifier::MESSAGE_INFORMATIONS);
        $taskData = (isset($data['jobs']) && isset($data['jobs'][$task->getId()])) ? $data['jobs'][$task->getId()] : [];

        return [
            'configuration' => $this->status->getStatus(),
            'actual'        => isset($taskData['status']) ? $taskData['status'] : Task::STATUS_STOPPED,
            'process-id'    => isset($taskData['process-id']) ? $taskData['process-id'] : null,
        ];
    }
}
