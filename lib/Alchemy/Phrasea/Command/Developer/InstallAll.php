<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
            ->setDescription('Installs all dependencies')
            ->addOption('no-dev', 'd', InputOption::VALUE_NONE, 'Do not install dev dependencies')
            ->addOption('prefer-source', 'p', InputOption::VALUE_NONE, 'Use the --prefer-source composer option')
            ->addOption('clear-cache', null, InputOption::VALUE_NONE, 'If defined forces to clear the cache before installation');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $ret = 0;

        $ret += $this->container['console']->get('dependencies:composer')->execute($input, $output);

        return min($ret, 255);
    }
}
