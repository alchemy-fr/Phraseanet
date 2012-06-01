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

use Symfony\Component\Console\Command\Command as SymfoCommand;
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
}
