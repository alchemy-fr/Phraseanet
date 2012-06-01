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
 * @todo write tests
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Command\Command;

class module_console_aboutLicense extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('This program license');

        return $this;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkSetup();

        $output->writeln(file_get_contents(__DIR__ . '/../../../../LICENSE'));

        return 0;
    }

    public function requireSetup()
    {
        return false;
    }
}
