<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\TaskManager\Log\LogFileFactory;
use Alchemy\Phrasea\TaskManager\Log\LogFileInterface;
use Alchemy\TaskManager\TaskManager;
use Assert\Assertion;
use Monolog\Handler\RotatingFileHandler;
use Neutron\SignalHandler\SignalHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class TaskManagerCommand extends Command
{
    /**
     * @return SignalHandler
     */
    protected function getSignalHandler()
    {
        return $this->container['signal-handler'];
    }

    /**
     * @return TaskManager
     */
    protected function getTaskManager()
    {
        return $this->container['task-manager'];
    }

    /**
     * @return array
     */
    protected function getLoggerConfiguration()
    {
        return $this->container['task-manager.logger.configuration'];
    }

    /**
     * @return LogFileFactory
     */
    protected function getTaskManagerLogFileFactory()
    {
        return $this->container['task-manager.log-file.factory'];
    }

    /**
     * @param callable $fileLocator Callable returning LogFileInterface when called
     */
    protected function configureLogger(callable $fileLocator)
    {
        $configuration = $this->getLoggerConfiguration();

        if ($configuration['enabled']) {
            /** @var LogFileInterface $file */
            $file = $fileLocator();
            Assertion::isInstanceOf($file, LogFileInterface::class);

            $handler = new RotatingFileHandler($file->getPath(), $configuration['max-files'], $configuration['level']);

            $this->getTaskManagerLogger()->pushHandler($handler);
        }
    }
}
