<?php

namespace Alchemy\Docker\Plugins\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize plugins');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
