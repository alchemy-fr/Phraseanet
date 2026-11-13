<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Event\TaskManagerSubscriber;

use Alchemy\TaskManager\Event\TaskManagerEvent;
use Alchemy\TaskManager\Event\TaskManagerEvents;
use Alchemy\TaskManager\Exception\InvalidArgumentException;
use Alchemy\TaskManager\LockFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Writes a lock file to prevent running the task manager multiple times concurrently.
 */
class LockFileSubscriber implements EventSubscriberInterface
{
    private $directory;
    private $logger;
    private $lockFile;

    public function __construct(?LoggerInterface $logger = null, $directory = null)
    {
        if (null === $directory || !is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('%s does not seem to be a directory.', $directory));
        }
        if (!is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('%s does not seem to be writeable.', $directory));
        }

        $this->logger = $logger;
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    public static function getSubscribedEvents()
    {
        return array(
            TaskManagerEvents::MANAGER_START => 'onManagerStart',
            TaskManagerEvents::MANAGER_STOP  => 'onManagerStop',
        );
    }

    public function onManagerStart(TaskManagerEvent $event)
    {
        $this->lockFile = new LockFile($this->getLockFilePath());
        $this->lockFile->lock();
    }

    public function onManagerStop(TaskManagerEvent $event)
    {
        if (null !== $this->lockFile) {
            $this->lockFile->unlock();
        }
    }

    private function getLockFilePath()
    {
        return $this->directory . '/task_manager.lock';
    }
}
