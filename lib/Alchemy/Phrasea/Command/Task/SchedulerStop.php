<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerStop extends Command
{
    public function __construct()
    {
        parent::__construct('scheduler:stop');
        $this->setDescription('Starts Phraseanet scheduler');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->container['task-manager.status']->stop();
        $output->writeln("Task manager has been toggled on stop, please be sure the process is running");
    }
}
