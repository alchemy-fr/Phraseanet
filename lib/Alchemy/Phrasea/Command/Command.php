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

use Monolog\Logger;
use Symfony\Component\Console\Command\Command as SymfoCommand;

/**
 * Abstract command which represents a Phraseanet base command
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class Command extends SymfoCommand
{

    /**
     * Constructor
     * @param type $name
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $core = \bootstrap::getCore();
        $this->logger = $core['monolog'];
    }

    /**
     * Set a logger to the command
     *
     * @param  Logger                           $logger
     * @return \Alchemy\Phrasea\Command\Command
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /** Get the current command logger
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
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
            $core = \bootstrap::getCore();

            if ( ! $core->getConfiguration()->isInstalled()) {
                throw new \RuntimeException('Phraseanet must be set-up');
            }
        }
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
            $duration = round($duration / 60  , 1) . ' minutes';
        } elseif ($duration > 3600) {
            $duration = round($duration / (60 * 60) , 1) . ' hours';
        } elseif ($duration > (24 * 60 * 60)) {
            $duration = round($duration / (24 * 60 * 60) , 1) . ' days';
        }

        return $duration;
    }
}
