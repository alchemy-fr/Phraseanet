<?php

namespace Alchemy\Docker\Plugins\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'init', description: 'Initialize plugins')]
class InitCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (glob('./plugins/*') as $dir) {
            if (is_dir($dir)) {
                $output->writeln(sprintf('Init <info>%s</info> plugin', basename($dir)));
                SubCommand::run(sprintf('bin/setup plugin:add %s', $dir));
            }
        }

        return 0;
    }
}
