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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\Console\Output\OutputInterface;

class task_Scheduler
{
    const TASKDELAYTOQUIT = 60;

    // how to schedule tasks (choose in 'run' method)
    const METHOD_FORK = 'METHOD_FORK';
    const METHOD_PROC_OPEN = 'METHOD_PROC_OPEN';

    private $method;
    private $input;
    protected $output;

    protected function log($message)
    {
        $registry = registry::get_instance();
        $logdir = $registry->get('GV_RootPath') . 'logs/';

        logs::rotate($logdir . "scheduler_l.log");
        logs::rotate($logdir . "scheduler_o.log");
        logs::rotate($logdir . "scheduler_e.log");

        $date_obj = new DateTime();
        $message = sprintf("%s\t%s", $date_obj->format(DATE_ATOM), $message);

        if ($this->input && ! ($this->input->getOption('nolog'))) {
            file_put_contents($logdir . "scheduler_l.log", $message . "\n", FILE_APPEND);
        }

        return $this;
    }

    protected static function get_connection()
    {
        return appbox::get_instance(\bootstrap::getCore())->get_connection();
    }

    public function run($input = null, OutputInterface $output = null) //, $log = true, $log_tasks = true)
    {
        $this->method = self::METHOD_FORK;

        require_once dirname(__FILE__) . '/../../bootstrap.php';
        $this->input = $input;
        $this->output = $output;
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $registry = $appbox->get_registry();

        $nullfile = '';
        $system = system_server::get_platform();
        switch ($system) {
            case "WINDOWS":
                $nullfile = 'NUL';
                break;
            default:
            case "DARWIN":
            case "LINUX":
                $nullfile = '/dev/null';
                break;
        }

        $lockdir = $registry->get('GV_RootPath') . 'tmp/locks/';

        for ($try = 0; true; $try ++ ) {
            if (($schedlock = fopen(($lockfile = ($lockdir . 'scheduler.lock')), 'a+'))) {
                if (flock($schedlock, LOCK_EX | LOCK_NB) === FALSE) {
                    $this->log(sprintf("failed to lock '%s' (try=%s/4)", $lockfile, $try));
                    if ($try == 4) {
                        $this->log("scheduler already running.");
                        fclose($schedlock);

                        return;
                    } else {
                        sleep(2);
                    }
                } else {
                    // locked
                    ftruncate($schedlock, 0);
                    fwrite($schedlock, '' . getmypid());
                    fflush($schedlock);
                    break;
                }
            }
        }

        $this->log(sprintf("running scheduler with method %s", $this->method));

        if ($this->method == self::METHOD_FORK)
            pcntl_signal(SIGCHLD, SIG_IGN);

        $logdir = $registry->get('GV_RootPath') . 'logs/';

        $conn = appbox::get_instance(\bootstrap::getCore())->get_connection();

        $taskPoll = array(); // the poll of tasks

        $sleeptime = 3;

        $sql = "UPDATE sitepreff SET schedstatus='started'";
        $conn->exec($sql);

        $task_manager = new task_manager($appbox);

        // set every 'auto-start' task to start
        foreach ($task_manager->get_tasks() as $task) {
            if ($task->is_active()) {
                $tid = $task->get_task_id();

                if ( ! $task->get_pid()) {
                    /* @var $task task_abstract */
                    $task->reset_crash_counter();
                    $task->set_status(task_abstract::STATUS_TOSTART);
                }
            }
        }

        $tlist = array();



        $schedstatus = 'started';
        $runningtask = 0;
        $connwaslost = false;

        $last_log_check = array();

        while ($schedstatus == 'started' || $runningtask > 0) {
            while (1) {
                try {
                    assert(is_object($conn));
                    $ping = @$conn->ping();
                } catch (ErrorException $e) {
                    $ping = false;
                }
                if ($ping)
                    break;

                unset($conn);
                if ( ! $connwaslost) {
                    $this->log(sprintf("Warning : abox connection lost, restarting in 10 min."));
                }
                for ($i = 0; $i < 60 * 10; $i ++ )
                    sleep(1);
                try {
                    $conn = appbox::get_instance(\bootstrap::getCore())->get_connection();
                } catch (ErrorException $e) {
                    $ping = false;
                }

                $connwaslost = true;
            }
            if ($connwaslost) {
                $this->log("abox connection restored");

                $sql = 'UPDATE task SET crashed=0';
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();

                $connwaslost = false;
            }
// printf("%d \n", __LINE__);
            $schedstatus = '';
            $row = NULL;
            try {
                $sql = "SELECT schedstatus FROM sitepreff";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
            } catch (ErrorException $e) {
                continue;
            }

            if ($row) {
                $schedstatus = $row["schedstatus"];
            }

            if ($schedstatus == 'tostop') {
                $sql = 'UPDATE sitepreff SET schedstatus = "stopping"';
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();

                // if scheduler is stopped, stop the tasks
                $sql = 'UPDATE task2 SET status="tostop" WHERE status != "stopped" and status != "manual"';
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
                $this->log("schedstatus == 'stopping', waiting tasks to end");
            }

            logs::rotate($logdir . "scheduler_t.log");
            logs::rotate($logdir . "scheduler_o.log");
            logs::rotate($logdir . "scheduler_e.log");

// printf("%d \n", __LINE__);
            // initialy, all tasks are supposed to be removed from the poll
            foreach ($taskPoll as $tkey => $task)
                $taskPoll[$tkey]["todel"] = true;

            foreach ($task_manager->get_tasks(true) as $task) {
                $tkey = "t_" . $task->get_task_id();
                $status = $task->get_status();

                logs::rotate($logdir . "task_t_" . $task->get_task_id() . ".log");
                logs::rotate($logdir . "task_o_" . $task->get_task_id() . ".log");
                logs::rotate($logdir . "task_e_" . $task->get_task_id() . ".log");

                if ( ! isset($taskPoll[$tkey])) {
                    // the task is not in the poll, add it
                    $phpcli = $registry->get('GV_cli');
                    switch ($system) {
                        case "WINDOWS":
                            $cmd = $phpcli;
                            $args = array('-f', $registry->get('GV_RootPath') . 'bin/console', '--', '-q', 'task:run', $task->get_task_id(), '--runner=scheduler');
                            if ($this->input && ($this->input->getOption('notasklog')))
                                $args[] = 'notasklog';
                            break;
                        default:
                        case "DARWIN":
                        case "LINUX":
                            $cmd = $phpcli;
                            $args = array('-f', $registry->get('GV_RootPath') . 'bin/console', '--', '-q', 'task:run', $task->get_task_id(), '--runner=scheduler');
                            if ($this->input && ($this->input->getOption('notasklog')))
                                $args[] = 'notasklog';
                            break;
                    }

                    $taskPoll[$tkey] = array(
                        "task"           => $task,
                        "current_status" => $status,
                        "cmd"            => $cmd,
                        "args"           => $args,
                        "killat"         => null,
                        "sigterm_sent"   => false
                    );
                    if ($this->method == self::METHOD_PROC_OPEN) {
                        $taskPoll[$tkey]['process'] = NULL;
                        $taskPoll[$tkey]['pipes'] = NULL;
                    }

                    $this->log(
                        sprintf(
                            "new Task %s, status=%s"
                            , $taskPoll[$tkey]["task"]->get_task_id()
                            , $status
                        )
                    );
                } else {
                    // the task is already in the poll, update its status
                    if ($taskPoll[$tkey]["current_status"] != $status) {
                        $this->log(
                            sprintf(
                                "Task %s, oldstatus=%s, newstatus=%s"
                                , $taskPoll[$tkey]["task"]->get_task_id()
                                , $taskPoll[$tkey]["current_status"]
                                , $status
                            )
                        );
                        $taskPoll[$tkey]["current_status"] = $status;
                    }
                    // update the whole task object
                    unset($taskPoll[$tkey]["task"]);
                    $taskPoll[$tkey]["task"] = $task;
                }

                unset($task);

                $taskPoll[$tkey]["todel"] = false; // this task exists, do not remove from poll
            }

            // remove not-existing task from poll
            foreach ($taskPoll as $tkey => $task) {
                if ($task["todel"]) {
                    $this->log(sprintf("Task %s deleted", $taskPoll[$tkey]["task"]->get_task_id()));
                    unset($taskPoll[$tkey]);
                }
            }

            // Launch task that are not yet launched
            $runningtask = 0;

            foreach ($taskPoll as $tkey => $tv) {
                $status = $tv['task']->get_status();
                switch ($status) {
                    default:
                        $this->log(sprintf('Unknow status `%s`', $status));
                        break;

                    case task_abstract::RETURNSTATUS_TORESTART:
                        if ( ! $taskPoll[$tkey]['task']->get_pid()) {
                            if ($this->method == self::METHOD_PROC_OPEN) {
                                @fclose($taskPoll[$tkey]["pipes"][1]);
                                @fclose($taskPoll[$tkey]["pipes"][2]);
                                @proc_close($taskPoll[$tkey]["process"]);

                                $taskPoll[$tkey]["process"] = null;
                            }
                            if ($schedstatus == 'started') {
                                $taskPoll[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
                            }
                            // trick to start the task immediatly : DON'T break if ending with 'tostart'
                            // so it will continue with 'tostart' case !
                        } else {
                            break;
                        }

                    case task_abstract::STATUS_TOSTART:
                        // if scheduler is 'tostop', don't launch a new task !
                        if ($schedstatus != 'started')
                            break;

                        $taskPoll[$tkey]["killat"] = NULL;

                        if ($this->method == self::METHOD_PROC_OPEN) {
                            if ( ! $taskPoll[$tkey]["process"]) {
                                $descriptors[1] = array('file', $logdir . "task_o_" . $task->get_task_id() . ".log", 'a+');
                                $descriptors[2] = array('file', $logdir . "task_e_" . $task->get_task_id() . ".log", 'a+');

                                $taskPoll[$tkey]["process"] = proc_open(
                                    $taskPoll[$tkey]["cmd"] . ' ' . implode(' ', $taskPoll[$tkey]["args"])
                                    , $descriptors
                                    , $taskPoll[$tkey]["pipes"]
                                    , $registry->get('GV_RootPath') . "bin/"
                                    , null
                                    , array('bypass_shell' => true)
                                );

                                if (is_resource($taskPoll[$tkey]["process"])) {
                                    sleep(2); // let the process lock and write it's pid
                                }

                                if (is_resource($taskPoll[$tkey]["process"]) && $taskPoll[$tkey]['task']->get_pid() !== null) {
                                    $this->log(
                                        sprintf(
                                            "Task %s '%s' started (pid=%s)"
                                            , $taskPoll[$tkey]['task']->get_task_id()
                                            , $taskPoll[$tkey]["cmd"]
                                            , $taskPoll[$tkey]['task']->get_pid()
                                        )
                                    );
                                    $runningtask ++;
                                } else {
                                    $taskPoll[$tkey]["task"]->increment_crash_counter();

                                    @fclose($taskPoll[$tkey]["pipes"][1]);
                                    @fclose($taskPoll[$tkey]["pipes"][2]);
                                    @proc_close($taskPoll[$tkey]["process"]);
                                    $taskPoll[$tkey]["process"] = null;

                                    $this->log(
                                        sprintf(
                                            "Task %s '%s' failed to start %d times"
                                            , $taskPoll[$tkey]["task"]->get_task_id()
                                            , $taskPoll[$tkey]["cmd"]
                                            , $taskPoll[$tkey]["task"]->get_crash_counter()
                                        )
                                    );

                                    if ($taskPoll[$tkey]["task"]->get_crash_counter() > 5)
                                        $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
                                    else
                                        $taskPoll[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
                                }
                            }
                        }
                        elseif ($this->method == self::METHOD_FORK) {
                            // printf("forking pid %d\n", getmypid());
                            $pid = pcntl_fork();
                            if ($pid == -1) {
                                die("failed to fork");
                            } elseif ($pid == 0) {
                                // child
                                // printf("hello i am child pid=%d\n", getmypid());
                                // printf("%s %s \n", $taskPoll[$tkey]["cmd"], implode(' ', $taskPoll[$tkey]["args"]));
                                umask(0);
                                if (posix_setsid() < 0)
                                    die("Forked process could not detach from terminal\n");

                                fclose(STDIN);
                                fclose(STDOUT);
                                fclose(STDERR);
                                $fdIN = fopen($nullfile, 'r');
                                $fdOUT = fopen($logdir . "task_o_" . $taskPoll[$tkey]["task"]->get_task_id() . ".log", 'a+');
                                $fdERR = fopen($logdir . "task_e_" . $taskPoll[$tkey]["task"]->get_task_id() . ".log", 'a+');

                                $this->log(sprintf("exec('%s %s')", $taskPoll[$tkey]["cmd"], implode(' ', $taskPoll[$tkey]["args"])));
                                pcntl_exec($taskPoll[$tkey]["cmd"], $taskPoll[$tkey]["args"]);
                            }
                            else {
                                // parent
                                // sleep(2);
                            }
                        }
                        break;

                    case task_abstract::STATUS_STARTED:

                        $crashed = false;
                        // If no process, the task is probably manually ran

                        if ($this->method == self::METHOD_PROC_OPEN) {
                            if ($taskPoll[$tkey]["process"]) {
                                $taskPoll[$tkey]["killat"] = NULL;

                                if (is_resource($taskPoll[$tkey]["process"])) {
                                    $proc_status = proc_get_status($taskPoll[$tkey]["process"]);
                                    if ($proc_status['running'])
                                        $runningtask ++;
                                    else
                                        $crashed = true;
                                }
                                else {
                                    $crashed = true;
                                }
                            }
                        }

                        if ( ! $crashed && ! $taskPoll[$tkey]['task']->get_pid())
                            $crashed = true;

                        if ( ! $crashed) {
                            $taskPoll[$tkey]["killat"] = NULL;
                            $runningtask ++;
                        } else {
                            // crashed !
                            $taskPoll[$tkey]["task"]->increment_crash_counter();

                            if ($this->method == self::METHOD_PROC_OPEN) {
                                @fclose($taskPoll[$tkey]["pipes"][1]);
                                @fclose($taskPoll[$tkey]["pipes"][2]);
                                @proc_close($taskPoll[$tkey]["process"]);
                                $taskPoll[$tkey]["process"] = null;
                            }
                            $this->log(
                                sprintf(
                                    "Task %s crashed %d times"
                                    , $taskPoll[$tkey]["task"]->get_task_id()
                                    , $taskPoll[$tkey]["task"]->get_crash_counter()
                                )
                            );

                            if ($taskPoll[$tkey]["task"]->get_crash_counter() > 5)
                                $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
                            else
                                $taskPoll[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
                        }
                        break;

                    case task_abstract::STATUS_TOSTOP:

                        if ($taskPoll[$tkey]["killat"] === NULL)
                            $taskPoll[$tkey]["killat"] = time() + self::TASKDELAYTOQUIT;

                        $pid = $taskPoll[$tkey]['task']->get_pid();
                        if ($pid) {
                            if ( ! $taskPoll[$tkey]['sigterm_sent']) {
                                posix_kill($pid, SIGTERM);
                                $this->log(
                                    sprintf(
                                        "SIGTERM sent to task %s (pid=%s)"
                                        , $taskPoll[$tkey]["task"]->get_task_id()
                                        , $pid
                                    )
                                );
                            }

                            if (($dt = $taskPoll[$tkey]["killat"] - time()) < 0) {
                                posix_kill($pid, 9);

                                $this->log(
                                    sprintf(
                                        "SIGKILL sent to task %s (pid=%s)"
                                        , $taskPoll[$tkey]["task"]->get_task_id()
                                        , $pid
                                    )
                                );

                                if ($this->method == self::METHOD_PROC_OPEN) {
                                    proc_terminate($taskPoll[$tkey]["process"], 9);
                                    @fclose($taskPoll[$tkey]["pipes"][1]);
                                    @fclose($taskPoll[$tkey]["pipes"][2]);
                                    proc_close($taskPoll[$tkey]["process"]);
                                }
                                unlink($lockdir . 'task_' . $taskPoll[$tkey]['task']->get_task_id() . '.lock');

                                $taskPoll[$tkey]["task"]->increment_crash_counter();
                                //                $taskPoll[$tkey]["task"]->set_pid(null);
                                $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
                            } else {
                                $this->log(
                                    sprintf(
                                        "waiting task %s to quit (kill in %d seconds)"
                                        , $taskPoll[$tkey]["task"]->get_task_id()
                                        , $dt
                                    )
                                );
                                $runningtask ++;
                            }
                        } else {
                            $this->log(
                                sprintf(
                                    "task %s has quit"
                                    , $taskPoll[$tkey]["task"]->get_task_id()
                                )
                            );
                            $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
                        }

                        break;

                    case task_abstract::RETURNSTATUS_STOPPED:
                    case task_abstract::RETURNSTATUS_TODELETE:
                        if ($this->method == self::METHOD_PROC_OPEN) {
                            if ($taskPoll[$tkey]["process"]) {
                                @fclose($taskPoll[$tkey]["pipes"][1]);
                                @fclose($taskPoll[$tkey]["pipes"][2]);
                                @proc_close($taskPoll[$tkey]["process"]);

                                $taskPoll[$tkey]["process"] = null;
                            }
                        }
                        break;
                }
            }

            for ($i = 0; $i < $sleeptime; $i ++ )
                sleep(1);
        }

        $sql = "UPDATE sitepreff SET schedstatus='stopped', schedpid='0'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $this->log("Scheduler2 is quitting.");

        ftruncate($schedlock, 0);
        fclose($schedlock);

        $this->log("Scheduler2 has quit.\n");
    }
}
