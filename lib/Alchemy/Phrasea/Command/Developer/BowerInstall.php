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
use vierbergenlars\SemVer\version;

class BowerInstall extends Command
{
    public function __construct()
    {
        parent::__construct('dependencies:bower');

        $this
            ->setDescription('Installs bower dependencies')
            ->addOption('no-dev', 'd', InputOption::VALUE_NONE, 'Do not install dev dependencies')
            ->addOption('clear-cache', null, InputOption::VALUE_NONE, 'If defined forces to clear the cache before installation');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $grunt = $this->container['driver.grunt'];
        $grunt->getProcessBuilderFactory()->setTimeout(600);

        $bower = $this->container['driver.bower'];

        $output->writeln("Using <info>".$grunt->getProcessBuilderFactory()->getBinary()."</info> for driver");
        $output->writeln("Using <info>".$bower->getProcessBuilderFactory()->getBinary()."</info> for driver");

        $version = trim($bower->command('-v'));

        if (version::lt($version, '1.0.0-alpha.1')) {
            throw new RuntimeException(sprintf(
                'Bower version 1.0.0-alpha.1 is required (version %s provided), please install bower-canary : `npm install -g bower-canary or run npm install from root directory`', $version
            ));
        }

        $version = trim($grunt->command('--version'));

        if (!version_compare('0.4.0', substr($version, -5), '<=')) {
            throw new RuntimeException(sprintf(
                'Grunt version >= 0.4.0 is required (version %s provided), please install grunt `http://gruntjs.com/getting-started`', $version
            ));
        }

        if ($input->getOption('clear-cache')) {
            $output->write("Cleaning bower cache... ");
            $bower->command(['cache', 'clean']);
            $output->writeln("<comment>OK</comment>");
        }

        try {
            $output->write("Installing assets...");
            $grunt->command('install-assets');
            $output->write(" <comment>OK</comment>");
            $output->writeln("");
            $this->container['console']->get('assets:compile-less')->execute($input, $output);
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Unable to install bower dependencies', $e->getCode(), $e);
        }

        return 0;
    }
}
