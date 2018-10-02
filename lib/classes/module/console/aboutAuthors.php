<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Command\Command;

class module_console_aboutAuthors extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Lists authors and contributors');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(file_get_contents(__DIR__ . '/../../../../AUTHORS'));

        return 0;
    }

}
