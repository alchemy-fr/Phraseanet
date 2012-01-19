<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
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
  protected $output;

  protected function log($message)
  {
    $registry = registry::get_instance();
    $logdir = $registry->get('GV_RootPath') . 'logs/';

    logs::rotate($logdir . "scheduler.log");

    $date_obj = new DateTime();
    $message = sprintf("%s\t%s", $date_obj->format(DATE_ATOM), $message);

    if($this->output instanceof OutputInterface)
    {
      $this->output->writeln($message);
    }
  //  else
    {
      file_put_contents($logdir . "scheduler.log", $message."\n", FILE_APPEND);
    }
    return $this;
  }

  protected static function get_connection()
  {
    require dirname(__FILE__) . '/../../../config/connexion.inc';

    return new connection_pdo('appbox', $hostname, $port, $user, $password, $dbname);
  }

  public function run(OutputInterface $output = null, $log_tasks = true)
  {
    $this->method = self::METHOD_FORK;

    require_once dirname(__FILE__) . '/../../bootstrap.php';
    $this->output = $output;
    $appbox = appbox::get_instance();
    $registry = $appbox->get_registry();

    $system = system_server::get_platform();

    $lockdir = $registry->get('GV_RootPath') . 'tmp/locks/';

    for($try = 0; true; $try++)
    {
      if(($schedlock = fopen(($lockfile = ($lockdir . 'scheduler.lock')), 'a+')))
      {
        if(flock($schedlock, LOCK_EX | LOCK_NB) === FALSE)
        {
          $this->log(sprintf("failed to lock '%s' (try=%s/4)", $lockfile, $try));
          if($try == 4)
          {
            $this->log("scheduler already running.");
            fclose($schedlock);

            return;
          }
          else
          {
            sleep(2);
          }
        }
        else
        {
          // locked
          ftruncate($schedlock, 0);
          fwrite($schedlock, '' . getmypid());
          fflush($schedlock);
          break;
        }
      }
    }
    
    $this->log(sprintf("running scheduler with method %s", $this->method));

    
    if($this->method == self::METHOD_FORK)
      pcntl_signal(SIGCHLD, SIG_IGN);

    $logdir = $registry->get('GV_RootPath') . 'logs/';

    $conn = self::get_connection();

    $taskPoll = array(); // the poll of tasks

    $sleeptime = 3;

    $sql = "UPDATE sitepreff SET schedstatus='started'";
    $conn->exec($sql);

    $task_manager = new task_manager($appbox);

    // set every 'auto-start' task to start
    foreach($task_manager->get_tasks() as $task)
    {
      if($task->is_active())
      {
        $tid = $task->get_task_id();

        if(!$task->get_pid())
        {
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

    while($schedstatus == 'started' || $runningtask > 0)
    {
      while(!$conn->ping())
      {
        unset($conn);
        if(!$connwaslost)
        {
          $this->log(sprintf("Warning : abox connection lost, restarting in 10 min."));
        }
        sleep(60 * 10);
        $conn = self::get_connection();
        $connwaslost = true;
      }
      if($connwaslost)
      {
        $this->log("abox connection restored");

        $sql = 'UPDATE task SET crashed=0';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $connwaslost = false;
      }

      $schedstatus = '';
      $sql = "SELECT schedstatus FROM sitepreff";
      $stmt = $conn->prepare($sql);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      if($row)
      {
        $schedstatus = $row["schedstatus"];
      }

      if($schedstatus == 'tostop')
      {
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

      logs::rotate($logdir . "scheduler.log");
      logs::rotate($logdir . "scheduler.error.log");


      // initialy, all tasks are supposed to be removed from the poll
      foreach($taskPoll as $tkey => $task)
        $taskPoll[$tkey]["todel"] = true;

      foreach($task_manager->get_tasks(true) as $task)
      {
        $tkey = "t_" . $task->get_task_id();

        logs::rotate($logdir . "task_$tkey.log");
        logs::rotate($logdir . "task_$tkey.error.log");

        if(!isset($taskPoll[$tkey]))
        {
          // the task is not in the poll, add it
          $phpcli = $registry->get('GV_cli');

          switch($system)
          {
            default:
            case "DARWIN":
            case "WINDOWS":
            case "LINUX":
              $cmd = $phpcli;
              $args = array('-f', $registry->get('GV_RootPath') . 'bin/console', 'task:run', $task->get_task_id(), '--runner=scheduler');
              break;
          }

          $taskPoll[$tkey] = array(
              "task" => $task,
              "current_status" => $task->get_status(),
              "cmd" => $cmd,
              "args" => $args,
              "killat" => null
          );
          if($this->method == self::METHOD_PROC_OPEN)
          {
            $taskPoll[$tkey]['process'] = NULL;
            $taskPoll[$tkey]['pipes'] = NULL;
          }

          $this->log(
                  sprintf(
                          "new Task %s, status=%s"
                          , $taskPoll[$tkey]["task"]->get_task_id()
                          , $task->get_status()
                  )
          );
        }
        else
        {
          // the task is already in the poll, update its status
          if($taskPoll[$tkey]["current_status"] != $task->get_status())
          {
            $this->log(
                    sprintf(
                            "Task %s, oldstatus=%s, newstatus=%s"
                            , $taskPoll[$tkey]["task"]->get_task_id()
                            , $taskPoll[$tkey]["current_status"]
                            , $task->get_status()
                    )
            );
            $taskPoll[$tkey]["current_status"] = $task->get_status();
          }
          // update the whole task object
          unset($taskPoll[$tkey]["task"]);
          $taskPoll[$tkey]["task"] = $task;
        }

        unset($task);

        $taskPoll[$tkey]["todel"] = false; // this task exists, do not remove from poll
      }

      // remove not-existing task from poll
      foreach($taskPoll as $tkey => $task)
      {
        if($task["todel"])
        {
          $this->log(sprintf("Task %s deleted", $taskPoll[$tkey]["task"]->get_task_id()));
          unset($taskPoll[$tkey]);
        }
      }


      /**
       * Launch task that are not yet launched
       */
      $runningtask = 0;

      foreach($taskPoll as $tkey => $tv)
      {
        switch($tv['task']->get_status())
        {
          default:
            $this->log(sprintf('Unknow status `%s`', $tv['task']->get_status()));
            break;

          case task_abstract::RETURNSTATUS_TORESTART:
            if(!$taskPoll[$tkey]['task']->get_pid())
            {
              if($this->method == self::METHOD_PROC_OPEN)
              {
                @fclose($taskPoll[$tkey]["pipes"][1]);
                @fclose($taskPoll[$tkey]["pipes"][2]);
                @proc_close($taskPoll[$tkey]["process"]);

                $taskPoll[$tkey]["process"] = null;
              }
              if($schedstatus == 'started')
              {
                $taskPoll[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
              }
              // trick to start the task immediatly : DON'T break if ending with 'tostart'
              // so it will continue with 'tostart' case !
            }
            else
            {
              break;
            }

          case task_abstract::STATUS_TOSTART:
            // if scheduler is 'tostop', don't launch a new task !
            if($schedstatus != 'started')
              break;

            $taskPoll[$tkey]["killat"] = NULL;

            if($this->method == self::METHOD_PROC_OPEN)
            {
              if(!$taskPoll[$tkey]["process"])
              {
                $descriptors = array(
                    1 => array("pipe", "w"),
                    2 => array("pipe", "w")
                );

                if($log_tasks === true)
                {
                  $descriptors[1] = array(
                      "file"
                      , $logdir . "task_$tkey.log"
                      , "a+"
                  );
                  $descriptors[2] = array(
                      "file"
                      , $logdir . "task_$tkey.error.log"
                      , "a+"
                  );
                }

                $taskPoll[$tkey]["process"] = proc_open(
                        $taskPoll[$tkey]["cmd"] . ' ' . implode(' ', $taskPoll[$tkey]["args"])
                        , $descriptors
                        , $taskPoll[$tkey]["pipes"]
                        , $registry->get('GV_RootPath') . "bin/"
                        , null
                        , array('bypass_shell' => true)
                );

                if(is_resource($taskPoll[$tkey]["process"]))
                {
                  sleep(2); // let the process lock and write it's pid
                }

                if(is_resource($taskPoll[$tkey]["process"]) && $taskPoll[$tkey]['task']->get_pid() !== null)
                {
                  $this->log(
                          sprintf(
                                  "Task %s '%s' started (pid=%s)"
                                  , $taskPoll[$tkey]['task']->get_task_id()
                                  , $taskPoll[$tkey]["cmd"]
                                  , $taskPoll[$tkey]['task']->get_pid()
                          )
                  );
                  $runningtask++;
                }
                else
                {
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

                  if($taskPoll[$tkey]["task"]->get_crash_counter() > 5)
                    $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
                  else
                    $taskPoll[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
                }
              }
            }
            elseif($this->method == self::METHOD_FORK)
            {
              // printf("forking pid %d\n", getmypid());
              $pid = pcntl_fork();
              if($pid == -1)
              {
                die("failed to fork");
              }
              elseif($pid == 0)
              {
                // child
                // printf("hello i am child pid=%d\n", getmypid());
                // printf("%s %s \n", $taskPoll[$tkey]["cmd"], implode(' ', $taskPoll[$tkey]["args"]));
                // ;
                umask(0);
                openlog('MyLog', LOG_PID | LOG_PERROR, LOG_LOCAL0);
                if(posix_setsid() < 0)
                  die("Forked process could not detach from terminal\n");
                //chdir(dirname(__FILE__));
                fclose(STDIN);
                fclose(STDOUT);
                fclose(STDERR);
                $fdIN = fopen('/dev/null', 'r');
                $fdOUT = fopen($logdir . "task_$tkey.log", 'a+');
                $fdERR = fopen($logdir . "task_$tkey.error.log", 'a+');

                pcntl_exec($taskPoll[$tkey]["cmd"], $taskPoll[$tkey]["args"]);

                sleep(2);
              }
              else
              {
                // parent
                // printf("hello i am parent pid=%d\n", getmypid());
              }
            }
            break;

          case task_abstract::STATUS_STARTED:

            $crashed = false;
            // If no process, the task is probably manually ran

            if($this->method == self::METHOD_PROC_OPEN)
            {
              if($taskPoll[$tkey]["process"])
              {
                $taskPoll[$tkey]["killat"] = NULL;

                if(is_resource($taskPoll[$tkey]["process"]))
                {
                  $proc_status = proc_get_status($taskPoll[$tkey]["process"]);
                  if($proc_status['running'])
                    $runningtask++;
                  else
                    $crashed = true;
                }
                else
                {
                  $crashed = true;
                }
              }
            }

            if(!$crashed && !$taskPoll[$tkey]['task']->get_pid())
              $crashed = true;

            if(!$crashed)
            {
              $taskPoll[$tkey]["killat"] = NULL;
              $runningtask++;
            }
            else
            {
              // crashed !
              $taskPoll[$tkey]["task"]->increment_crash_counter();

              if($this->method == self::METHOD_PROC_OPEN)
              {
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

              if($taskPoll[$tkey]["task"]->get_crash_counter() > 5)
                $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
              else
                $taskPoll[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
            }
            break;

          case task_abstract::STATUS_TOSTOP:

            if($taskPoll[$tkey]["killat"] === NULL)
              $taskPoll[$tkey]["killat"] = time() + self::TASKDELAYTOQUIT;

            $tpid = $taskPoll[$tkey]['task']->get_pid();
            if($tpid)
            {
              if(($dt = $taskPoll[$tkey]["killat"] - time()) < 0)
              {
                if($this->method == self::METHOD_PROC_OPEN)
                {
                  $pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $tpid`);
                  foreach($pids as $pid)
                  {
                    if(is_numeric($pid))
                    {
                      $this->log("Killing pid %d", $pid);
                      posix_kill($pid, 9);
                    }
                  }
                }
                elseif($this->method == self::METHOD_FORK)
                {
                  posix_kill($tpid, 9);
                }

                $this->log(
                        sprintf(
                                "SIGKILL sent to task %s (pid=%s)"
                                , $taskPoll[$tkey]["task"]->get_task_id()
                                , $tpid
                        )
                );

                if($this->method == self::METHOD_PROC_OPEN)
                {
                  proc_terminate($taskPoll[$tkey]["process"], 9);
                  @fclose($taskPoll[$tkey]["pipes"][1]);
                  @fclose($taskPoll[$tkey]["pipes"][2]);
                  proc_close($taskPoll[$tkey]["process"]);
                }
                unlink($lockdir . 'task_' . $taskPoll[$tkey]['task']->get_task_id() . '.lock');

                $taskPoll[$tkey]["task"]->increment_crash_counter();
                //                $taskPoll[$tkey]["task"]->set_pid(null);
                $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
              }
              else
              {
                $this->log(
                        sprintf(
                                "waiting task %s to quit (kill in %d seconds)"
                                , $taskPoll[$tkey]["task"]->get_task_id()
                                , $dt
                        )
                );
                $runningtask++;
              }
            }
            else
            {
              $this->log(
                      sprintf(
                              "task %s has quit"
                              , $taskPoll[$tkey]["task"]->get_task_id()
                      )
              );
            }

            break;

          case task_abstract::RETURNSTATUS_STOPPED:
          case task_abstract::RETURNSTATUS_TODELETE:
            if($this->method == self::METHOD_PROC_OPEN)
            {
              if($taskPoll[$tkey]["process"])
              {
                @fclose($taskPoll[$tkey]["pipes"][1]);
                @fclose($taskPoll[$tkey]["pipes"][2]);
                @proc_close($taskPoll[$tkey]["process"]);

                $taskPoll[$tkey]["process"] = null;
              }
            }
            break;
        }
      }


      /*


        $common_status = array(
        task_abstract::STATUS_STARTED
        , task_abstract::RETURNSTATUS_STOPPED
        );


        foreach($taskPoll as $tkey => $tv)
        {
        //        if (!in_array($taskPoll[$tkey]["task"]->get_status(), $common_status))
        //        {
        //          $this->log(
        //                  sprintf(
        //                          'task %s has status %s'
        //                          , $taskPoll[$tkey]["task"]->get_task_id()
        //                          , $taskPoll[$tkey]["task"]->get_status()
        //                  )
        //          );
        //        }
        switch($tv['task']->get_status())
        {
        default:
        $this->log(sprintf('Unknow status `%s`', $tv['task']->get_status()));
        break;

        case task_abstract::RETURNSTATUS_TORESTART:
        if(!$taskPoll[$tkey]['task']->get_pid())
        {
        @fclose($taskPoll[$tkey]["pipes"][1]);
        @fclose($taskPoll[$tkey]["pipes"][2]);
        @proc_close($taskPoll[$tkey]["process"]);

        $taskPoll[$tkey]["process"] = null;
        if($schedstatus == 'started')
        {
        $taskPoll[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
        }
        // trick to start the task immediatly : DON'T break if ending with 'tostart'
        // so it will continue with 'tostart' case !
        }
        else
        {
        break;
        }

        case task_abstract::STATUS_TOSTART:
        // if scheduler is 'tostop', don't launch a new task !
        if($schedstatus != 'started')
        break;

        $taskPoll[$tkey]["killat"] = NULL;
        if(!$taskPoll[$tkey]["process"])
        {
        $descriptors = array(
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
        );

        if($log_tasks === true)
        {
        $descriptors[1] = array(
        "file"
        , $logdir . "task_$tkey.log"
        , "a+"
        );
        $descriptors[2] = array(
        "file"
        , $logdir . "task_$tkey.error.log"
        , "a+"
        );
        }

        $taskPoll[$tkey]["process"] = proc_open(
        $taskPoll[$tkey]["cmd"].' '.implode(' ', $taskPoll[$tkey]["args"])
        , $descriptors
        , $taskPoll[$tkey]["pipes"]
        , $registry->get('GV_RootPath') . "bin/"
        , null
        , array('bypass_shell' => true)
        );

        if(is_resource($taskPoll[$tkey]["process"]))
        {
        sleep(2); // let the process lock and write it's pid
        }

        if(is_resource($taskPoll[$tkey]["process"]) && $taskPoll[$tkey]['task']->get_pid() !== null)
        {

        // ************************************************
        //                file_put_contents("/tmp/scheduler2.log", sprintf("%s [%d] : RUNNING ? : pid=%s \n", __FILE__, __LINE__, $taskPoll[$tkey]['task']->get_pid()), FILE_APPEND);

        $this->log(
        sprintf(
        "Task %s '%s' started (pid=%s)"
        , $taskPoll[$tkey]['task']->get_task_id()
        , $taskPoll[$tkey]["cmd"]
        , $taskPoll[$tkey]['task']->get_pid()
        )
        );
        $runningtask++;
        }
        else
        {
        // ************************************************
        //                file_put_contents("/tmp/scheduler2.log", sprintf("%s [%d] : NOT RUNNING ? : pid=%s \n", __FILE__, __LINE__, $taskPoll[$tkey]['task']->get_pid()), FILE_APPEND);
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

        if($taskPoll[$tkey]["task"]->get_crash_counter() > 5)
        {
        $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
        }
        else
        {
        $taskPoll[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
        }
        }
        }
        break;

        case task_abstract::STATUS_STARTED:
        $crashed = false;
        // If no process, the task is probably manually ran
        if($taskPoll[$tkey]["process"])
        {
        $taskPoll[$tkey]["killat"] = NULL;

        //                if(is_resource($taskPoll[$tkey]["process"]))
        //                {
        //                $proc_status = proc_get_status($taskPoll[$tkey]["process"]);
        //                if($proc_status['running'])
        //                $runningtask++;
        //                else
        //                $crashed = true;
        //                }
        //                else
        //                {
        //                $crashed = true;
        //                }

        if($taskPoll[$tkey]['task']->get_pid())
        $runningtask++;
        else
        $crashed = true;
        }

        if($crashed === true && $taskPoll[$tkey]["task"]->get_status() === task_abstract::RETURNSTATUS_TORESTART)
        {
        $crashed = false;
        }
        if($crashed)
        {
        $taskPoll[$tkey]["task"]->increment_crash_counter();

        @fclose($taskPoll[$tkey]["pipes"][1]);
        @fclose($taskPoll[$tkey]["pipes"][2]);
        @proc_close($taskPoll[$tkey]["process"]);
        $taskPoll[$tkey]["process"] = null;

        $this->log(
        sprintf(
        "Task %s crashed %d times"
        , $taskPoll[$tkey]["task"]->get_task_id()
        , $taskPoll[$tkey]["task"]->get_crash_counter()
        )
        );


        if($taskPoll[$tkey]["task"]->get_crash_counter() > 5)
        {
        $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
        }
        else
        {
        $taskPoll[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
        }
        }
        break;

        case task_abstract::STATUS_TOSTOP:
        if($taskPoll[$tkey]["process"])
        {
        if($taskPoll[$tkey]["killat"] === NULL)
        $taskPoll[$tkey]["killat"] = time() + self::TASKDELAYTOQUIT;

        if(($dt = $taskPoll[$tkey]["killat"] - time()) < 0)
        {
        $ppid = $taskPoll[$tkey]['task']->get_pid();
        $pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $ppid`);
        foreach($pids as $pid)
        {
        if(is_numeric($pid))
        {
        $this->log("Killing pid %d", $pid);
        posix_kill($pid, 9);
        }
        }

        $this->log(
        sprintf(
        "SIGKILL sent to task %s (pid=%s)"
        , $taskPoll[$tkey]["task"]->get_task_id()
        , $taskPoll[$tkey]["task"]->get_pid()
        )
        );

        proc_terminate($taskPoll[$tkey]["process"], 9);
        @fclose($taskPoll[$tkey]["pipes"][1]);
        @fclose($taskPoll[$tkey]["pipes"][2]);
        proc_close($taskPoll[$tkey]["process"]);
        unlink($lockdir . 'task_' . $taskPoll[$tkey]['task']->get_task_id() . '.lock');

        $taskPoll[$tkey]["task"]->increment_crash_counter();
        //                $taskPoll[$tkey]["task"]->set_pid(null);
        $taskPoll[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
        }
        else
        {
        $this->log(
        sprintf(
        "waiting task %s to quit (kill in %d seconds)"
        , $taskPoll[$tkey]["task"]->get_task_id()
        , $dt
        )
        );
        $runningtask++;
        }
        }
        break;

        case task_abstract::RETURNSTATUS_STOPPED:
        case task_abstract::RETURNSTATUS_TODELETE:
        if($taskPoll[$tkey]["process"])
        {
        @fclose($taskPoll[$tkey]["pipes"][1]);
        @fclose($taskPoll[$tkey]["pipes"][2]);
        @proc_close($taskPoll[$tkey]["process"]);

        $taskPoll[$tkey]["process"] = null;
        }
        break;
        }
        }

       */










      $to_reopen = false;
      if($conn->ping())
      {
        $conn->close();
        unset($conn);
        $to_reopen = true;
      }
      sleep($sleeptime);
      if($to_reopen)
      {
        $conn = self::get_connection();
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
