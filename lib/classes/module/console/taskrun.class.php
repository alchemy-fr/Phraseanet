<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Command\Command;
use Monolog\Handler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @todo write tests
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
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
            'syslog', null, InputOption::VALUE_REQUIRED, //::VALUE_OPTIONAL,
            'threshold : (DEBUG|INFO|WARNING|ERROR|CRITICAL|ALERT)', null
        );
        $this->addOption(
            'maillog', null, InputOption::VALUE_REQUIRED, //::VALUE_OPTIONAL,
            'threshold : (DEBUG|INFO|WARNING|ERROR|CRITICAL|ALERT)', null
        );
//        $this->addOption(
//            'nolog'
//            , NULL
//            , 1 | InputOption::VALUE_NONE
//            , 'do not log to logfile'
//            , NULL
//        );
        $this->setDescription('Run task');

        return $this;
    }

    public function sig_handler($signo)
    {
        if ($this->task) {
            $this->task->log(sprintf("signal %s received", $signo));
            $this->task->setRunning(false);
        }
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

        $task_id = (int) $input->getArgument('task_id');
        if ($task_id <= 0 || strlen($task_id) !== strlen($input->getArgument('task_id'))) {
            throw new \RuntimeException('Argument must be an Id.');
        }

        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $task_manager = new task_manager($appbox);

        if ($input->getOption('runner') === task_abstract::RUNNER_MANUAL) {
            $schedStatus = $task_manager->getSchedulerState();

            if ($schedStatus && $schedStatus['status'] == task_abstract::STATE_STARTED && $schedStatus['pid']) {
                $this->shedulerPID = $schedStatus['pid'];
            }
            $runner = task_abstract::RUNNER_MANUAL;
        } else {
            $runner = task_abstract::RUNNER_SCHEDULER;
            $schedStatus = $task_manager->getSchedulerState();
            if ($schedStatus && $schedStatus['status'] == task_abstract::STATE_STARTED && $schedStatus['pid']) {
                $this->shedulerPID = $schedStatus['pid'];
            }
        }
        $core = \bootstrap::getCore();

        $logger = $core['monolog'];

        $syslogOption = strtoupper($core['task.config']->get('SyslogLevel'));
        $maillogOption = strtoupper($core['task.config']->get('MaillogLevel'));

        if ($syslogOption != '' || $maillogOption != '') {
            $lib2v = array(
                'DEBUG'       => Logger::DEBUG,
                'INFO'        => Logger::INFO,
                'WARNING'     => Logger::WARNING,
                'ERROR'       => Logger::ERROR,
                'CRITICAL'    => Logger::CRITICAL,
                'ALERT'       => Logger::ALERT
            );
            if ($syslogOption != '') {
                if (!array_key_exists($syslogOption, $lib2v)) {
                    throw(new RuntimeException(sprintf(
                            "Bad value '%s' for setting SyslogLevel\nuse DEBUG|INFO|WARNING|ERROR|CRITICAL|ALERT", $syslogOption))
                    );
                }
                $fakeTask = $task_manager->getTask($task_id, null);
                $handler = new Handler\SyslogHandler(
                        "Phraseanet-Task/" . $fakeTask->getName(), // string added to each message
                        "user", // facility (type of program logging)
                        $lib2v[$syslogOption], // level
                        true        // bubble
                );
                unset($fakeTask);
                $logger->pushHandler($handler);
            }
            if ($maillogOption != '') {
                if (!array_key_exists($maillogOption, $lib2v)) {
                    throw(new RuntimeException(sprintf(
                            "Bad value '%s' for setting MaillogLevel\nuse DEBUG|INFO|WARNING|ERROR|CRITICAL|ALERT", $maillogOption))
                    );
                }
                $registry = registry::get_instance();
                $adminMail = $registry->get('GV_adminMail');
                $senderMail = $registry->get('GV_defaultmailsenderaddr');
                $handler = new Handler\NativeMailerHandler(
                        $adminMail,
                        "Task problem",
                        $senderMail,
                        $lib2v[$maillogOption], // level
                        true
                );
                $logger->pushHandler($handler);
            }
        }


        $logfile = __DIR__ . '/../../../../logs/task_' . $task_id . '.log';
        $handler = new Handler\RotatingFileHandler($logfile, 10);
        $logger->pushHandler($handler);

        $this->task = $task_manager->getTask($task_id, $logger);

        register_tick_function(array($this, 'tick_handler'), true);
        declare(ticks = 1);

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, array($this, 'sig_handler'));
            pcntl_signal(SIGINT, array($this, 'sig_handler'));
            //   pcntl_signal(SIGKILL, array($this, 'sig_handler'));
        }

        try {
            $this->task->run($runner);
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
                if (function_exists('posix_kill') && !posix_kill($this->shedulerPID, 0)) {
                    if (method_exists($this->task, 'signal')) {
                        $this->task->signal('SIGNAL_SCHEDULER_DIED');
                    } else {
                        $this->task->setState(task_abstract::STATE_TOSTOP);
                    }
                }

                $start = time();
            }
        }
    }
}
