<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class InstallAll extends Command
{
    public function __construct()
    {
        parent::__construct('dependencies:all');

        $this
            ->setDescription('Install all dependencies')
            ->addOption('no-dev', 'd', InputOption::VALUE_NONE, 'Do not install dev dependencies')
            ->addOption('prefer-source', 'p', InputOption::VALUE_NONE, 'Use the --prefer-source composer option')
            ->addOption('attempts', 'a', InputOption::VALUE_REQUIRED, 'Number of attempts to install bower dependencies.', 4);
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $ret = 0;

        $ret += $this->container['console']->get('dependencies:bower')->execute($input, $output);
        $ret += $this->container['console']->get('dependencies:composer')->execute($input, $output);
        $ret += $this->container['console']->get('assets:build-javascript')->execute($input, $output);
        $ret += $this->container['console']->get('assets:compile-less')->execute($input, $output);

        return min($ret, 255);
    }
}
