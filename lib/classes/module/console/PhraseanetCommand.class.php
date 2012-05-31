<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

abstract class module_console_PhraseanetCommand extends Command
{

    /**
     * Tell whether the command requires Phraseanet to be set up to run
     * @return boolean
     */
    abstract public function needPhraseaInstalled();

    /**
     * Check if Phraseanet is set up and if the current commands requires
     * Phraseanet to be installed
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return boolean
     */
    public function checkPhraseaInstall(OutputInterface $output)
    {
        if ($this->needPhraseaInstalled()) {
            $core = \bootstrap::getCore();

            if ( ! $core->getConfiguration()->isInstalled()) {
                $output->writeln("<error>Phraseanet is not set up</error>");

                return false;
            }
        }

        return true;
    }

}