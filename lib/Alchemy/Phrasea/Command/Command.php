<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Application;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Console\Command\Command as SymfoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract command which represents a Phraseanet base command
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class Command extends SymfoCommand
{
    /**
     * @var Application
     */
    protected $container = null;

    /**
     * Constructor
     * @param type $name
     */
    public function __construct($name)
    {
        parent::__construct($name);
    }

    /**
     * Tell whether the command requires Phraseanet to be set-up or not
     *
     * @return Boolean
     */
    abstract public function requireSetup();

    /**
     * Check if Phraseanet is set-up and if the current command requires
     * Phraseanet to be set-up
     *
     * @throws \RuntimeException
     * @return Boolean
     */
    public function checkSetup()
    {
        if ($this->requireSetup()) {
            if ( ! $this->container['phraseanet.core']->getConfiguration()->isInstalled()) {
                throw new \RuntimeException('Phraseanet must be set-up');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkSetup();

        if ($input->getOption('verbose')) {
            $handler = new StreamHandler('php://stdout');
            $this->container['phraseanet.core']['monolog']->pushHandler($handler);
        }

        return $this->doExecute($input, $output);
    }

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
     * @return ServiceProvider
     */
    public function getService($name)
    {
        return isset($this->container[$name]) ? $this->container[$name] : null;
    }

    /**
     * Format a duration in seconds to human readable
     *
     * @param  type   $seconds the time to format
     * @return string
     */
    public function getFormattedDuration($seconds)
    {
        $duration = ceil($seconds) . ' seconds';

        if ($duration > 60) {
            $duration = round($duration / 60, 1) . ' minutes';
        } elseif ($duration > 3600) {
            $duration = round($duration / (60 * 60), 1) . ' hours';
        } elseif ($duration > (24 * 60 * 60)) {
            $duration = round($duration / (24 * 60 * 60), 1) . ' days';
        }

        return $duration;
    }
}
