<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Compile;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Configuration extends Command
{
    public function __construct()
    {
        parent::__construct('compile:configuration');

        $this->setDescription('Compiles YAML configuration to plain PHP');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->container['configuration.store']->compileAndWrite();

        $output->writeln("Configuration compiled.");

        return 0;
    }
}
