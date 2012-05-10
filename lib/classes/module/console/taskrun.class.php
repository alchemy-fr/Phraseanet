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
 * @todo write tests
 *
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class module_console_taskrun extends Command
{
    private $task;
    private $shedulerPID;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->task = NULL;
        $this->shedulerPID = NULL;

        $this->addArgument('task_id', InputArgument::REQUIRED, 'The task_id to run');
        $this->addOption(
            'runner'
            , 'r'
            , InputOption::VALUE_REQUIRED
            , 'The name of the runner (manual, scheduler...)'
            , task_abstract::RUNNER_MANUAL
        );
        $this->addOption(
            'nolog'
            , NULL
            , 1 | InputOption::VALUE_NONE
            , 'do not log to logfile'
            , NULL
        );
        $this->setDescription('Run task');

        return $this;
    }

    function sig_handler($signo)
    {
        if ($this->task) {
            $this->task->log(sprintf("signal %s received", $signo));
            if ($signo == SIGTERM)
                $this->task->setRunning(false);
        }
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! setup::is_installed()) {
            $output->writeln('Phraseanet is not set up');
            return 1;
        }

        $task_id = (int) $input->getArgument('task_id');
        if ($task_id <= 0 || strlen($task_id) !== strlen($input->getArgument('task_id')))
            throw new \RuntimeException('Argument must be an Id.');

        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $task_manager = new task_manager($appbox);
        $this->task = $task_manager->getTask($task_id);

        if ($input->getOption('runner') === task_abstract::RUNNER_MANUAL) {
            $schedStatus = $task_manager->getSchedulerState();

            if ($schedStatus && $schedStatus['status'] == 'running' && $schedStatus['pid'])
                $this->shedulerPID = $schedStatus['pid'];
            $runner = task_abstract::RUNNER_MANUAL;
        }
        else {
            $runner = task_abstract::RUNNER_SCHEDULER;
            $schedStatus = $task_manager->getSchedulerState();
            if ($schedStatus && $schedStatus['status'] == 'running' && $schedStatus['pid'])
                $this->shedulerPID = $schedStatus['pid'];
        }

        register_tick_function(array($this, 'tick_handler'), true);
        declare(ticks = 1);
        if (function_exists('pcntl_signal'))
            pcntl_signal(SIGTERM, array($this, 'sig_handler'));

        try {
            $this->task->run($runner, $input, $output);
        } catch (Exception $e) {
            $this->task->log(sprintf("taskrun : exception from 'run()', %s \n", $e->getMessage()));
            return($e->getCode());
        }

        if ($input->getOption('runner') === task_abstract::RUNNER_MANUAL) {
            $runner = task_abstract::RUNNER_MANUAL;
        }
    }

    public function tick_handler()
    {
        static $start = FALSE;

        if ($start === FALSE) {
            $start = time();
        }

        if (time() - $start > 0) {
            if ($this->shedulerPID) {
                if ( ! posix_kill($this->shedulerPID, 0)) {
                    if (method_exists($this->task, 'signal'))
                        $this->task->signal('SIGNAL_SCHEDULER_DIED');
                    else
                        $this->task->setState(task_abstract::STATE_TOSTOP);
                }


                $start = time();
            }
        }
    }
}
