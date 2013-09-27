<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Doctrine\Common\Persistence\ObjectManager;
use Entities\Task;

class TaskManipulator implements ManipulatorInterface
{
    /** @var Objectmanager */
    private $om;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Creates a Task.
     *
     * @param string $name
     * @param string $jobFqn
     * @param string $settings
     * @param integer $period
     *
     * @return Task
     */
    public function create($name, $jobFqn, $settings, $period)
    {
        $task = new Task();
        $task->setName($name)
            ->setClassname($jobFqn)
            ->setSettings($settings)
            ->setPeriod($period);

        $this->om->persist($task);
        $this->om->flush();

        return $task;
    }

    /**
     * Updates a Task in the manager.
     *
     * @param Task $task
     *
     * @return Task
     */
    public function update(Task $task)
    {
        $this->om->persist($task);
        $this->om->flush();

        return $task;
    }

    /**
     * Deletes a task.
     *
     * @param Task $task
     */
    public function delete(Task $task)
    {
        $this->om->remove($task);
        $this->om->flush();
    }

    /**
     * Sets the task status to "started".
     *
     * @param Task $task
     *
     * @return Task
     */
    public function start(Task $task)
    {
        $task->setStatus(Task::STATUS_STARTED);

        $this->om->persist($task);
        $this->om->flush();

        // send ZMQ message

        return $task;
    }

    /**
     * Sets the task status to "stopped".
     *
     * @param Task $task
     *
     * @return Task
     */
    public function stop(Task $task)
    {
        $task->setStatus(Task::STATUS_STOPPED);

        $this->om->persist($task);
        $this->om->flush();

        // send ZMQ message

        return $task;
    }

    /**
     * Reset the number of crashes of the task.
     *
     * @param Task $task
     *
     * @return Task
     */
    public function resetCrashes(Task $task)
    {
        $task->setCrashed(0);

        $this->om->persist($task);
        $this->om->flush();

        // send ZMQ message

        return $task;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->om->getRepository('Entities\Task');
    }
}
