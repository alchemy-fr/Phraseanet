<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanTaskItemsCommand extends Command
{
    public function __construct()
    {
        parent::__construct('clean:task-items');

        $this
            ->setDescription('clean:task-items')
            ->addOption('input-dir_since',       null, InputOption::VALUE_REQUIRED,                             'Input dir to clean')
            ->addOption('older_than',       null, InputOption::VALUE_REQUIRED,                             '')
            ->addOption('dry-run',        null, InputOption::VALUE_NONE,                                 'dry run')

            ->setHelp('');
    }

    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        print_r(scandir('.'));
    }
}
