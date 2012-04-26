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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

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
        $output->writeln(file_get_contents(__DIR__ . '/../../../../LICENSE'));

        return 0;
    }
}
