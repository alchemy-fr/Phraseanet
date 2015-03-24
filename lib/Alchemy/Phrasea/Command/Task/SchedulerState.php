<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerState extends Command
{
    const EXITCODE_ERROR = 1;
    const EXITCODE_STARTED = 10;
    const EXITCODE_STOPPED = 11;

    public function __construct()
    {
        parent::__construct('task-manager:scheduler:state');
        $this
            ->setDescription('Returns scheduler state')
            ->addOption('short', null, InputOption::VALUE_NONE, 'print short result, ie: <info>stopped()</info> | <info>started(12345)</info> | <info>tostop(12345)</info> | <info>stopping(12345)</info>');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->container['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $info = $this->container['task-manager.live-information']->getManager();
        $error = $info['configuration'] !== $info['actual'];
        $actual = $error ? "<error>" .$info['actual']. "</error>" : "<info>".$info['actual']."</info>";
        $configuration = $error ? "<comment>".$info['configuration']."</comment>" : "<info>".$info['configuration']."</info>";

        if (null === $info['process-id']) {
            if ($input->getOption('short')) {
                $output->writeln(sprintf('%s', $actual));
            } else {
                $output->writeln(sprintf('Scheduler is %s (configured with `%s`)', $actual, $configuration));
            }
        } else {
            if ($input->getOption('short')) {
                $output->writeln(sprintf('%s(%s)', $actual, $info['process-id']));
            } else {
                $output->writeln(sprintf('Scheduler is %s (configured with `%s`) with process-id %d', $actual, $configuration, $info['process-id']));
            }
        }

        switch ($info['actual']) {
            case Task::STATUS_STARTED:
                return self::EXITCODE_STARTED;
            case Task::STATUS_STOPPED:
                return self::EXITCODE_STOPPED;
            default:
                return self::EXITCODE_ERROR;
        }
    }
}
