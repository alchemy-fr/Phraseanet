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
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use vierbergenlars\SemVer\version;

class BowerInstall extends Command
{
    public function __construct()
    {
        parent::__construct('dependencies:bower');

        $this
            ->setDescription('Installs bower dependencies')
            ->addOption('no-dev', 'd', InputOption::VALUE_NONE, 'Do not install dev dependencies')
            ->addOption('attempts', 'a', InputOption::VALUE_REQUIRED, 'Number of attempts to install dependencies.', 4);
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $bower = $this->container['driver.bower'];
        $output->writeln("Using <info>".$bower->getProcessBuilderFactory()->getBinary()."</info> for driver");

        $version = trim($bower->command('-v'));

        if (version::lt($version, '1.0.0-alpha.1')) {
            throw new RuntimeException(sprintf(
                'Bower version 1.0.0-alpha.1 is required (version %s provided), please install bower-canary : `npm install -g bower-canary`', $version
            ));
        }

        $attempts = $input->getOption('attempts');

        if (0 >= $attempts) {
            throw new InvalidArgumentException('Attempts number should be a positive value.');
        }

        $output->write("Cleaning bower cache... ");
        $bower->command(array('cache', 'clean'));
        $output->writeln("<info>OK</info>");

        $output->write("Removing assets... ");
        $this->container['filesystem']->remove($this->container['root.path'] . '/www/assets');
        $output->writeln("<info>OK</info>");

        $success = false;
        $n = 1;
        while ($attempts > 0) {
            try {
                $output->write("\rInstalling assets (attempt #$n)...");
                $bower->command($input->getOption('no-dev') ? array('install', '--production') : 'install');
                $success = true;
                $output->writeln("<info>OK</info>");
                break;
            } catch (ExecutionFailureException $e) {
                $attempts--;
                $n++;
            }
        }

        if (!$success) {
            throw new RuntimeException('Unable to install bower dependencies');
        }

        return 0;
    }
}
