<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Monolog\Logger;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_Scheduler
{
    const TASKDELAYTOQUIT = 60;
    // how to schedule tasks (choose in 'run' method)
    const METHOD_FORK = 'METHOD_FORK';
    const METHOD_PROC_OPEN = 'METHOD_PROC_OPEN';
    const ERR_ALREADY_RUNNING = 114;   // aka EALREADY (Operation already in progress)

    /**
     *
     * @var \Monolog\Logger
     */
    private $logger;
    private $method;
    private $dependencyContainer;

    public function __construct(Application $application, Logger $logger)
    {
        $this->dependencyContainer = $application;
        $this->logger = $logger;
    }

    protected function log($message)
    {
        $this->logger->addInfo($message);

        return $this;
    }

    /**
     * @throws Exception if scheduler is already running
     * @todo doc all possible exception
     */
    public function run()
    {
        //prevent scheduler to fail if GV_cli is not provided
        if ( ! is_executable($this->dependencyContainer['phraseanet.registry']->get('php_binary'))) {
            throw new \RuntimeException('PHP cli is not provided in registry');
        }

        $this->method = self::METHOD_PROC_OPEN;

        $nullfile = '/dev/null';

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $nullfile = 'NUL';
        }

        if (\task_manager::isPosixPcntlSupported()) {
            // avoid <defunct> php when a task ends
            pcntl_signal(SIGCHLD, SIG_IGN);
            $this->method = self::METHOD_FORK;
        }

        $lockdir = $this->dependencyContainer['phraseanet.registry']->get('GV_RootPath') . 'tmp/locks/';

        for ($try = 1; true; $try ++ ) {
            $lockfile = ($lockdir . 'scheduler.lock');
            if (($schedlock = fopen($lockfile, 'a+')) != FALSE) {
                if (flock($schedlock, LOCK_EX | LOCK_NB) === FALSE) {
                    $this->log(sprintf("failed to lock '%s' (try=%s/4)", $lockfile, $try));
                    if ($try == 4) {
                        $this->log("scheduler already running.");
                        fclose($schedlock);

                        throw new Exception('scheduler already running.', self::ERR_ALREADY_RUNNING);

                        return;
                    } else {
                        sleep(2);
                    }
                } else {
                    // locked
                    ftruncate($schedlock, 0);
                    fwrite($schedlock, '' . getmypid());
                    fflush($schedlock);

                    // for windows : unlock then lock shared to allow OTHER processes to read the file
                    // too bad : no critical section nor atomicity
                    flock($schedlock, LOCK_UN);
                    flock($schedlock, LOCK_SH);
                    break;
                }
            }
        }

        $this->log(sprintf("running scheduler with method %s", $this->method));

        $conn = $this->dependencyContainer['phraseanet.appbox']->get_connection();

        $taskPoll = array(); // the poll of tasks

        $sleeptime = 3;

        $sql = "UPDATE sitepreff SET schedstatus='started'";
        $conn->exec($sql);

        $task_manager = new task_manager($this->dependencyContainer);

        // set every 'auto-start' task to start
        foreach ($task_manager->getTasks() as $task) {
            if ($task->isActive()) {
                if ( ! $task->getPID()) {
                    /* @var $task task_abstract */
                    $task->resetCrashCounter();
                    $task->setState(task_abstract::STATE_TOSTART);
                }
            }
        }

        $schedstatus = 'started';
        $runningtask = 0;
        $connwaslost = false;

        while ($schedstatus == 'started' || $runningtask > 0) {
            while (1) {
                try {
                    assert(is_object($conn));
                    $ping = @$conn->ping();
                } catch (ErrorException $e) {
                    $ping = false;
                }
                if ($ping) {
                    break;
                }

                unset($conn);
                if ( ! $connwaslost) {
                    $this->log(sprintf("Warning : abox connection lost, restarting in 10 min."));
                }
                for ($i = 0; $i < 60 * 10; $i ++ ) {
                    sleep(1);
                }
                try {
                    $conn = $this->dependencyContainer['phraseanet.appbox']->get_connection();
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

            // initialy, all tasks are supposed to be removed from the poll
            foreach ($taskPoll as $tkey => $task) {
                $taskPoll[$tkey]["todel"] = true;
            }

            foreach ($task_manager->getTasks(true) as $task) {
                $tkey = "t_" . $task->getID();
                $status = $task->getState();

                if ( ! isset($taskPoll[$tkey])) {
                    // the task is not in the poll, add it
                    $taskPoll[$tkey] = array(
                        "task"           => $task,
                        "current_status" => $status,
                        "cmd"            => $this->dependencyContainer['phraseanet.registry']->get('php_binary'),
                        "args"           => array(
                            '-f',
                            $this->dependencyContainer['phraseanet.registry']->get('GV_RootPath') . 'bin/console',
                            '--',
                            '-q',
                            'task:run',
                            $task->getID(), '--runner=scheduler'
                        ),
                        "killat"       => null,
                        "sigterm_sent" => false,
                        "pid"          => false
                    );
                    if ($this->method == self::METHOD_PROC_OPEN) {
                        $taskPoll[$tkey]['process'] = NULL;
                        $taskPoll[$tkey]['pipes'] = NULL;
                    }

                    $this->log(
                        sprintf(
                            "new Task %s, status=%s"
                            , $taskPoll[$tkey]["task"]->getID()
                            , $status
                        )
                    );
                } else {
                    // the task is already in the poll, update its status
                    if ($taskPoll[$tkey]["current_status"] != $status) {
                        $this->log(
                            sprintf(
                                "Task %s, oldstatus=%s, newstatus=%s"
                                , $taskPoll[$tkey]["task"]->getID()
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
                    $this->log(sprintf("Task %s deleted", $taskPoll[$tkey]["task"]->getID()));
                    unset($taskPoll[$tkey]);
                }
            }

            // Launch task that are not yet launched
            $runningtask = 0;

            foreach ($taskPoll as $tkey => $tv) {
                $status = $tv['task']->getState();
                switch ($status) {
                    default:
                        $this->log(sprintf('Unknow status `%s`', $status));
                        break;

                    case task_abstract::STATE_TORESTART:
                        if ( ! $taskPoll[$tkey]['task']->getPID()) {
                            if ($this->method == self::METHOD_PROC_OPEN) {
                                @fclose($taskPoll[$tkey]["pipes"][1]);
                                @fclose($taskPoll[$tkey]["pipes"][2]);
                                @proc_close($taskPoll[$tkey]["process"]);

                                $taskPoll[$tkey]["process"] = null;
                            } elseif ($this->method == self::METHOD_FORK) {
                                $pid = $taskPoll[$tkey]['pid'];
                                if ($pid) {
                                    $status = NULL;
                                    if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                                        // pid has quit
                                        $taskPoll[$tkey]['pid'] = false;
                                    }
                                }
                            }

                            if ($schedstatus == 'started') {
                                $taskPoll[$tkey]["task"]->setState(task_abstract::STATE_TOSTART);
                            }
                            // trick to start the task immediatly : DON'T break if ending with 'tostart'
                            // so it will continue with 'tostart' case !
                        } else {
                            break;
                        }

                    case task_abstract::STATE_TOSTART:
                        // if scheduler is 'tostop', don't launch a new task !
                        if ($schedstatus != 'started') {
                            break;
                        }

                        $taskPoll[$tkey]["killat"] = NULL;

                        if ($this->method == self::METHOD_PROC_OPEN) {
                            if ( ! $taskPoll[$tkey]["process"]) {

                                $nullfile = defined('PHP_WINDOWS_VERSION_BUILD') ? 'NUL' : '/dev/null';

                                $descriptors[1] = array('file', $nullfile, 'a+');
                                $descriptors[2] = array('file', $nullfile, 'a+');

                                $taskPoll[$tkey]["process"] = proc_open(
                                    escapeshellarg($taskPoll[$tkey]["cmd"]) . ' ' . implode(' ', array_map('escapeshellarg', $taskPoll[$tkey]["args"]))
                                    , $descriptors
                                    , $taskPoll[$tkey]["pipes"]
                                    , $this->dependencyContainer['phraseanet.registry']->get('GV_RootPath') . "bin/"
                                    , null
                                    , array('bypass_shell' => true)
                                );

                                if (is_resource($taskPoll[$tkey]["process"])) {
                                    sleep(2); // let the process lock and write it's pid
                                }

                                if (is_resource($taskPoll[$tkey]["process"]) && ($pid = $taskPoll[$tkey]['task']->getPID()) !== null) {
                                    $taskPoll[$tkey]['pid'] = $pid;
                                    $this->log(
                                        sprintf(
                                            "Task %s '%s' started (pid=%s)"
                                            , $taskPoll[$tkey]['task']->getID()
                                            , $taskPoll[$tkey]["cmd"] . ' ' . implode(' ', $taskPoll[$tkey]["args"])
                                            , $pid
                                        )
                                    );
                                    $runningtask ++;
                                } else {
                                    $taskPoll[$tkey]["task"]->incrementCrashCounter();

                                    @fclose($taskPoll[$tkey]["pipes"][1]);
                                    @fclose($taskPoll[$tkey]["pipes"][2]);
                                    @proc_close($taskPoll[$tkey]["process"]);
                                    $taskPoll[$tkey]["process"] = null;

                                    $this->log(
                                        sprintf(
                                            "Task %s '%s' failed to start %d times"
                                            , $taskPoll[$tkey]["task"]->getID()
                                            , $taskPoll[$tkey]["cmd"]
                                            , $taskPoll[$tkey]["task"]->getCrashCounter()
                                        )
                                    );

                                    if ($taskPoll[$tkey]["task"]->getCrashCounter() > 5) {
                                        $taskPoll[$tkey]["task"]->setState(task_abstract::STATE_STOPPED);
                                    } else {
                                        $taskPoll[$tkey]["task"]->setState(task_abstract::STATE_TOSTART);
                                    }
                                }
                            }
                        } elseif ($this->method == self::METHOD_FORK) {
                            $pid = pcntl_fork();
                            if ($pid == -1) {
                                die("failed to fork");
                            } elseif ($pid == 0) {
                                umask(0);
                                if (posix_setsid() < 0) {
                                    die("Forked process could not detach from terminal\n");
                                }

                                // todo (if possible) : redirecting stdin, stdout to log files ?

                                $this->log(sprintf("exec('%s %s')", $taskPoll[$tkey]["cmd"], implode(' ', $taskPoll[$tkey]["args"])));
                                pcntl_exec($taskPoll[$tkey]["cmd"], $taskPoll[$tkey]["args"]);
                            } else {
                                // parent (scheduler)
                                $taskPoll[$tkey]['pid'] = $pid;
                            }
                        }
                        break;

                    case task_abstract::STATE_STARTED:
                        $crashed = false;
                        // If no process, the task is probably manually ran

                        if ($this->method == self::METHOD_PROC_OPEN) {
                            if ($taskPoll[$tkey]["process"]) {
                                $taskPoll[$tkey]["killat"] = NULL;

                                if (is_resource($taskPoll[$tkey]["process"])) {
                                    $proc_status = proc_get_status($taskPoll[$tkey]["process"]);
                                    if ($proc_status['running']) {
                                        $runningtask ++;
                                    } else {
                                        $crashed = true;
                                    }
                                } else {
                                    $crashed = true;
                                }
                            }
                        }

                        if ( ! $crashed && ! $taskPoll[$tkey]['task']->getPID()) {
                            $crashed = true;
                        }

                        if ( ! $crashed) {
                            $taskPoll[$tkey]["killat"] = NULL;
                            $runningtask ++;
                        } else {
                            // crashed !
                            $taskPoll[$tkey]["task"]->incrementCrashCounter();

                            if ($this->method == self::METHOD_PROC_OPEN) {
                                @fclose($taskPoll[$tkey]["pipes"][1]);
                                @fclose($taskPoll[$tkey]["pipes"][2]);
                                @proc_close($taskPoll[$tkey]["process"]);
                                $taskPoll[$tkey]["process"] = null;
                            }
                            $this->log(
                                sprintf(
                                    "Task %s crashed %d times"
                                    , $taskPoll[$tkey]["task"]->getID()
                                    , $taskPoll[$tkey]["task"]->getCrashCounter()
                                )
                            );

                            if ($taskPoll[$tkey]["task"]->getCrashCounter() > 5) {
                                $taskPoll[$tkey]["task"]->setState(task_abstract::STATE_STOPPED);
                            } else {
                                $taskPoll[$tkey]["task"]->setState(task_abstract::STATE_TOSTART);
                            }
                        }
                        break;

                    case task_abstract::STATE_TOSTOP:

                        if ($taskPoll[$tkey]["killat"] === NULL) {
                            $taskPoll[$tkey]["killat"] = time() + self::TASKDELAYTOQUIT;
                        }

                        $pid = $taskPoll[$tkey]['task']->getPID();
                        if ($pid) {
                            // send ctrl-c to tell the task to CLEAN quit
                            // (just in case the task doesn't pool his status 'tostop' fast enough)
                            if (function_exists('posix_kill')) {
                                if ( ! $taskPoll[$tkey]['sigterm_sent']) {
                                    posix_kill($pid, SIGTERM);
                                    $this->log(
                                        sprintf(
                                            "SIGTERM sent to task %s (pid=%s)"
                                            , $taskPoll[$tkey]["task"]->getID()
                                            , $pid
                                        )
                                    );
                                }
                            }

                            if (($dt = $taskPoll[$tkey]["killat"] - time()) < 0) {
                                // task still alive, time to kill
                                if ($this->method == self::METHOD_PROC_OPEN) {
                                    proc_terminate($taskPoll[$tkey]["process"], 9);
                                    @fclose($taskPoll[$tkey]["pipes"][1]);
                                    @fclose($taskPoll[$tkey]["pipes"][2]);
                                    proc_close($taskPoll[$tkey]["process"]);
                                    $this->log(
                                        sprintf(
                                            "proc_terminate(...) done on task %s (pid=%s)"
                                            , $taskPoll[$tkey]["task"]->getID()
                                            , $pid
                                        )
                                    );
                                } else { // METHOD_FORK, I guess we have posix
                                    posix_kill($pid, 9);
                                    $this->log(
                                        sprintf(
                                            "SIGKILL sent to task %s (pid=%s)"
                                            , $taskPoll[$tkey]["task"]->getID()
                                            , $pid
                                        )
                                    );
                                }
                            } else {
                                $this->log(
                                    sprintf(
                                        "waiting task %s to quit (kill in %d seconds)"
                                        , $taskPoll[$tkey]["task"]->getID()
                                        , $dt
                                    )
                                );
                                $runningtask ++;
                            }
                        } else {
                            $this->log(
                                sprintf(
                                    "task %s has quit"
                                    , $taskPoll[$tkey]["task"]->getID()
                                )
                            );
                            $taskPoll[$tkey]["task"]->setState(task_abstract::STATE_STOPPED);
                        }

                        break;

                    case task_abstract::STATE_STOPPED:
                    case task_abstract::STATE_TODELETE:
                        if ($this->method == self::METHOD_PROC_OPEN) {
                            if ($taskPoll[$tkey]["process"]) {
                                @fclose($taskPoll[$tkey]["pipes"][1]);
                                @fclose($taskPoll[$tkey]["pipes"][2]);
                                @proc_close($taskPoll[$tkey]["process"]);

                                $taskPoll[$tkey]["process"] = null;
                            }
                        } elseif ($this->method == self::METHOD_FORK) {
                            $pid = $taskPoll[$tkey]['pid'];
                            if ($pid) {
                                $status = NULL;
                                if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                                    // pid has quit
                                    $taskPoll[$tkey]['pid'] = false;
                                }
                            }
                        }
                        break;
                }
            }

            for ($i = 0; $i < $sleeptime; $i ++ ) {
                sleep(1);
            }
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
