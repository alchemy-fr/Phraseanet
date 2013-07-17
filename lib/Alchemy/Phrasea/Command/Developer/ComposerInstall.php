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
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ComposerInstall extends Command
{
    public function __construct()
    {
        parent::__construct('dependencies:composer');

        $this
            ->setDescription('Installs composer dependencies')
            ->addOption('no-dev', 'd', InputOption::VALUE_NONE, 'Do not install dev dependencies')
            ->addOption('prefer-source', 'p', InputOption::VALUE_NONE, 'Use the --prefer-source composer option');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->container['driver.composer'];

        $output->write("Updating composer... ");
        $composer->command('self-update');
        $output->writeln("<info>OK</info>");

        $commands = array('install', '--optimize-autoloader');
        if ($input->getOption('prefer-source')) {
            $commands[] = '--prefer-source';
        }

        try {
            if ($input->getOption('no-dev')) {
                $output->write("Installing dependencies <info>without</info> developer packages ");
                $composer->command(array_merge($commands, array('--no-dev')));
                $output->writeln("<info>OK</info>");
            } else {
                $output->write("Installing dependencies <info>with</info> developer packages ");
                $composer->command(array_merge($commands, array('--dev')));
                $output->writeln("<info>OK</info>");
            }
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Unable to install composer dependencies', $e->getCode(), $e);
        }

        return 0;
    }
}
