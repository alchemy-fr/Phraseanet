<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\TaskManager\Job\EmptyCollectionJob;
use Alchemy\Phrasea\TaskManager\NotifierInterface;
use Alchemy\Phrasea\TaskManager\NullNotifier;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Translation\TranslatorInterface;

class TaskManipulator implements ManipulatorInterface
{
    /** @var NotifierInterface */
    private $notifier;
    /** @var ObjectManager */
    private $om;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(ObjectManager $om, TranslatorInterface $translator, NotifierInterface $notifier = null)
    {
        $this->om = $om;
        $this->translator = $translator;
        $this->setNotifier($notifier);
    }

    public function setNotifier(NotifierInterface $notifier = null)
    {
        $this->notifier = $notifier ?: new NullNotifier();
        return $this;
    }

    /**
     * Creates a Task.
     *
     * @param string  $name
     * @param string  $jobId
     * @param string  $settings
     * @param integer $period
     *
     * @return Task
     */
    public function create($name, $jobId, $settings, $period)
    {
        $task = new Task();
        $task->setName($name)
            ->setJobId($jobId)
            ->setSettings($settings)
            ->setPeriod($period);

        $this->om->persist($task);
        $this->om->flush();

        $this->notify(NotifierInterface::MESSAGE_CREATE);

        return $task;
    }

    /**
     * Creates a EmptyCollection task given a collection
     *
     * @param \collection $collection
     *
     * @return Task
     */
    public function createEmptyCollectionJob(\collection $collection)
    {
        $job = new EmptyCollectionJob($this->translator);
        $settings = simplexml_load_string($job->getEditor()->getDefaultSettings());
        $settings->bas_id = $collection->get_base_id();

        $task = new Task();
        $task->setName($job->getName())
            ->setJobId($job->getJobId())
            ->setSettings($settings->asXML())
            ->setPeriod($job->getEditor()->getDefaultPeriod());

        $this->om->persist($task);
        $this->om->flush();

        $this->notify(NotifierInterface::MESSAGE_CREATE);

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

        $this->notify(NotifierInterface::MESSAGE_UPDATE);

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

        $this->notify(NotifierInterface::MESSAGE_DELETE);
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

        $this->notify(NotifierInterface::MESSAGE_UPDATE);

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

        $this->notify(NotifierInterface::MESSAGE_UPDATE);

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

        $this->notify(NotifierInterface::MESSAGE_UPDATE);

        return $task;
    }

    private function notify($message)
    {
        try {
            $this->notifier->notify($message);
        } catch (RuntimeException $e) {
        }
    }
}
