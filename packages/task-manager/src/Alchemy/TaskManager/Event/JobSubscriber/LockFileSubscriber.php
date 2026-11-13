<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Event\JobSubscriber;

use Alchemy\TaskManager\Event\JobEvent;
use Alchemy\TaskManager\Event\JobEvents;
use Alchemy\TaskManager\Exception\InvalidArgumentException;
use Alchemy\TaskManager\LockFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Writes a lock file to prevent running the same job multiple concurrently.
 */
class LockFileSubscriber implements EventSubscriberInterface
{
    private $id;
    private $directory;
    private $logger;
    private $lockFile;

    public function __construct($id, ?LoggerInterface $logger = null, $directory = null)
    {
        if (null === $directory || !is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('%s does not seem to be a directory.', $directory));
        }
        if (!is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('%s does not seem to be writeable.', $directory));
        }
        if ('' === $id = trim((string) $id)) {
            throw new InvalidArgumentException('The id can not be empty.');
        }

        $this->id = (string) $id;
        $this->logger = $logger;
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    public static function getSubscribedEvents()
    {
        return array(
            JobEvents::START     => 'onJobStart',
            JobEvents::STOP      => 'onJobStop',
            JobEvents::EXCEPTION => 'onJobStop',
        );
    }

    public function onJobStart(JobEvent $event)
    {
        $this->lockFile = new LockFile($this->getLockFilePath());
        $this->lockFile->lock();
    }

    public function onJobStop(JobEvent $event)
    {
        if (null !== $this->lockFile) {
            $this->lockFile->unlock();
        }
    }

    private function getLockFilePath()
    {
        return $this->directory . '/task_' . $this->id . '.lock';
    }
}
