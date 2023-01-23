<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Application;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command as SymfoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfoCommand implements CommandInterface
{
    /**
     * @var Application
     */
    protected $container = null;

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_QUIET) {
            switch ($output->getVerbosity()) {
                default:
                case OutputInterface::VERBOSITY_NORMAL:
                    $level = Logger::WARNING;
                    break;
                case OutputInterface::VERBOSITY_VERBOSE:
                    $level = Logger::NOTICE;
                    break;
                case OutputInterface::VERBOSITY_VERY_VERBOSE:
                    $level = Logger::INFO;
                    break;
                case OutputInterface::VERBOSITY_DEBUG:
                    $level = Logger::DEBUG;
                    break;
            }
            $handler = new StreamHandler('php://stdout', $level);

            $pushHandler = function (Logger $logger) use ($handler) {
                return $logger->pushHandler($handler);
            };

            $this->container['monolog'] = $this->container->share(
                $this->container->extend('monolog', $pushHandler)
            );
            $this->container['task-manager.logger'] = $this->container->share(
                $this->container->extend('task-manager.logger', $pushHandler)
            );
        }

        return $this->doExecute($input, $output);
    }

    abstract protected function doExecute(InputInterface $input, OutputInterface $output);

    /**
     * Sets the application container containing all services.
     *
     * @param Application $container Application object to register.
     *
     * @return void
     */
    public function setContainer(Application $container)
    {
        $this->container = $container;
    }

    /**
     * Returns the application container.
     *
     * @return Application
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Returns a service contained in the application container or null if none
     * is found with that name.
     *
     * This is a convenience method used to retrieve an element from the
     * Application container without having to assign the results of the
     * getContainer() method in every call.
     *
     * @param string $name Name of the service
     *
     * @see self::getContainer()
     *
     * @return mixed
     */
    public function getService($name)
    {
        return isset($this->container[$name]) ? $this->container[$name] : null;
    }

    /**
     * Format a duration in seconds to human readable
     *
     * @param int $seconds the time to format
     * @return string
     */
    public function getFormattedDuration($seconds)
    {
        if ($seconds > (24 * 3600)) {
            $duration = round($seconds / (24 * 3600), 1) . ' days';
        }
        elseif ($seconds > 3600) {
            $duration = round($seconds / (3600), 1) . ' hours';
        }
        elseif ($seconds > 60) {
            $duration = round($seconds / 60, 1) . ' minutes';
        }
        else {
            $duration = ceil($seconds) . ' seconds';
        }

        return $duration;
    }

    /**
     * {@inheritdoc}
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @return Logger
     */
    protected function getTaskManagerLogger()
    {
        return $this->container['task-manager.logger'];
    }
}
