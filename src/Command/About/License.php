<?php

namespace App\Command\About;

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
use Symfony\Component\Console\Command\Command;

class License extends Command
{

    protected function configure()
    {
        $this
            ->setName('about:license')
            ->setDescription('Displays this program license.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(file_get_contents(__DIR__ . '/../../../LICENSE'));
    }
}
