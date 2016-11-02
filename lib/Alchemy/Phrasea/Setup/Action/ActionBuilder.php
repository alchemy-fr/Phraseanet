<?php

namespace Alchemy\Phrasea\Setup\Action;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface ActionBuilder
{

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return Action
     */
    public function createAction(InputInterface $input, OutputInterface $output);
}
