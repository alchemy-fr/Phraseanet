<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class module_console_schedulerState extends Command
{
    const EXITCODE_SETUP_ERROR = 1;
    const EXITCODE_STATE_UNKNOWN = 21;

    private $stateToExitCode = array(
        \task_manager::STATE_TOSTOP   => 13,
        \task_manager::STATE_STARTED  => 10,
        \task_manager::STATE_STOPPING => 12,
        \task_manager::STATE_STOPPED  => 11,
    );

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Get scheduler status');

        $this->addOption(
            'short'
            , NULL
            , InputOption::VALUE_NONE
            , 'print short result, ie: <info>stopped()</info> | <info>started(12345)</info> | <info>tostop(12345)</info> | <info>stopping(12345)</info>'
            , NULL
        );

        return $this;
    }

    public function requireSetup()
    {
        return false;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->checkSetup();
        } catch (\RuntimeException $e) {
            return self::EXITCODE_SETUP_ERROR;
        }

        $appbox = $this->getService('phraseanet.appbox');
        $task_manager = new task_manager($appbox);

        $exitCode = 0;
        $state = $task_manager->getSchedulerState();

        if ($input->getOption('short')) {
            $output->writeln(sprintf('%s(%s)', $state['status'], $state['pid']));
        } else {
            if ($state['pid'] != NULL) {
                $output->writeln(sprintf(
                        'Scheduler is %s on pid %d'
                        , $state['status']
                        , $state['pid']
                    ));
            } else {
                $output->writeln(sprintf('Scheduler is %s', $state['status']));
            }
        }

        if (array_key_exists($state['status'], $this->stateToExitCode)) {
            $exitCode = $this->stateToExitCode[$state['status']];
        } else {
            $exitCode = self::EXITCODE_STATE_UNKNOWN;
        }

        return $exitCode;
    }
}
