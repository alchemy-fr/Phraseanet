<?php

namespace Vendor;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CustomCommand extends Command
{
    public function __construct()
    {
        parent::__construct('hello:world');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('Hello World');

        return 0;
    }
}
