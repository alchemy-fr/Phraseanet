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

  protected $output;

  protected function log($message)
  {
    if ($this->output instanceof OutputInterface)
    {
      $this->output->writeln($message);
    }

    $registry = registry::get_instance();
    $logdir = $registry->get('GV_RootPath') . 'logs/';

    logs::rotate($logdir . "scheduler.log");

    $date_obj = new DateTime();
    $message = sprintf("%s %s \n", $date_obj->format(DATE_ATOM), $message);

    file_put_contents($logdir . "scheduler.log", $message, FILE_APPEND);

    return $this;
  }

  protected static function get_connection()
  {
    require dirname(__FILE__) . '/../../../config/connexion.inc';

    return new connection_pdo('appbox', $hostname, $port, $user, $password, $dbname);
  }

  public function run(OutputInterface $output = null, $log_tasks = true)
  {
    require_once dirname(__FILE__) . '/../../bootstrap.php';
    $this->output = $output;
    $appbox = appbox::get_instance();
    $registry = $appbox->get_registry();

    $system = system_server::get_platform();

    $lockdir = $registry->get('GV_RootPath') . 'tmp/locks/';

    for ($try = 0; true; $try++)
    {
      $schedlock = fopen(($lockfile = ($lockdir . 'scheduler.lock')), 'a+');
      if (flock($schedlock, LOCK_EX | LOCK_NB) != true)
      {
        $this->log(sprintf("failed to lock '%s' (try=%s/4)", $lockfile, $try));
        if ($try == 4)
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
        ftruncate($schedlock, 0);
        fwrite($schedlock, '' . getmypid());
        fflush($schedlock);
        break;
      }
    }

    $logdir = $registry->get('GV_RootPath') . 'logs/';

    $conn = self::get_connection();

    $ttask = array();

    $sleeptime = 3;

    $sql = "UPDATE sitepreff SET schedstatus='started', schedpid = :pid";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':pid' => getmypid()));
    $stmt->closeCursor();


    $task_manager = new task_manager($appbox);

    $tlist = array();
    foreach ($task_manager->get_tasks() as $task)
    {
      if (!$task->is_active())
      {
        continue;
      }
      
      $tid = $task->get_task_id();

      if (!$task->is_running())
      {
        /* @var $task task_abstract */
        $task->reset_crash_counter();
        $task->set_status(task_abstract::STATUS_TOSTART);
      }
    }


    $schedstatus = 'started';
    $runningtask = 0;
    $connwaslost = false;

    $last_log_check = array();

    while ($schedstatus == 'started' || $runningtask > 0)
    {
      while (!$conn->ping())
      {
        unset($conn);
        if (!$connwaslost)
        {
          $this->log(sprintf("Warning : abox connection lost, restarting in 10 min."));
        }
        sleep(60 * 10);
        $conn = self::get_connection();
        $connwaslost = true;
      }
      if ($connwaslost)
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

      if ($row)
      {
        $schedstatus = $row["schedstatus"];
      }

      if ($schedstatus == 'tostop')
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


      /**
       * potentially, all tasks are supposed to be removed
       */
      foreach ($ttask as $tkey => $tv)
      {
        $ttask[$tkey]["todel"] = true;
      }

      $sql = "SELECT * FROM task2";
      $stmt = $conn->prepare($sql);
      $stmt->execute();
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($task_manager->get_tasks(true) as $task)
      {
        $tkey = "t_" . $task->get_task_id();

        logs::rotate($logdir . "task_$tkey.log");
        logs::rotate($logdir . "task_$tkey.error.log");

        if (!isset($ttask[$tkey]))
        {
          $phpcli = $registry->get('GV_cli');

          switch ($system)
          {
            default:
            case "DARWIN":
            case "WINDOWS":
            case "LINUX":
              $cmd = $phpcli . ' -f '
                      . $registry->get('GV_RootPath')
                      . "bin/console task:run "
                      . $task->get_task_id()
                      . " --runner=scheduler ";
              break;
          }

          $ttask[$tkey] = array(
              "task" => $task,
              "current_status" => $task->get_status(),
              "process" => null,
              "cmd" => $cmd,
              "killat" => null,
              "pipes" => null
          );
          $this->log(
                  sprintf(
                          "new Task %s, status=%s"
                          , $ttask[$tkey]["task"]->get_task_id()
                          , $task->get_status()
                  )
          );
        }
        else
        {
          if ($ttask[$tkey]["current_status"] != $task->get_status())
          {
            $this->log(
                    sprintf(
                            "Task %s, oldstatus=%s, newstatus=%s"
                            , $ttask[$tkey]["task"]->get_task_id()
                            , $ttask[$tkey]["current_status"]
                            , $task->get_status()
                    )
            );
            $ttask[$tkey]["current_status"] = $task->get_status();
          }

          $ttask[$tkey]["task"] = $task;
        }
        $ttask[$tkey]["todel"] = false;
      }

      foreach ($ttask as $tkey => $tv)
      {
        if ($tv["todel"])
        {
          $this->log(sprintf("Task %s deleted", $ttask[$tkey]["task"]->get_task_id()));
          unset($ttask[$tkey]);
        }
      }


      /**
       * Launch task that are not yet launched
       */
      $runningtask = 0;
      foreach ($ttask as $tkey => $tv)
      {
        $this->log(
                sprintf(
                        'task %s has status %s'
                        , $ttask[$tkey]["task"]->get_task_id()
                        , $ttask[$tkey]["task"]->get_status()
                )
        );
        switch ($ttask[$tkey]["task"]->get_status())
        {
          case 'torestart':
            @fclose($ttask[$tkey]["pipes"][1]);
            @fclose($ttask[$tkey]["pipes"][2]);
            @proc_close($ttask[$tkey]["process"]);

            $ttask[$tkey]["process"] = null;
            if ($schedstatus == 'started')
            {
              $ttask[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
            }
            break;
          case 'tostart':
            $ttask[$tkey]["killat"] = NULL;
            if ($schedstatus == 'started' && !$ttask[$tkey]["process"])
            {
              $descriptors = array(
                  1 => array("pipe", "w")
                  , 2 => array("pipe", "w")
              );

              if ($log_tasks === true)
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

              $ttask[$tkey]["process"] = proc_open(
                      $ttask[$tkey]["cmd"]
                      , $descriptors
                      , $ttask[$tkey]["pipes"]
                      , $registry->get('GV_RootPath') . "bin/"
                      , null
                      , array('bypass_shell' => true)
              );

              if (is_resource($ttask[$tkey]["process"]))
              {
                $proc_status = proc_get_status($ttask[$tkey]["process"]);
                if ($proc_status['running'])
                  $ttask[$tkey]['task']->set_pid($proc_status['pid']);
              }

              if ($ttask[$tkey]['task']->get_pid() !== null)
              {
                $this->log(
                        sprintf(
                                "Task %s '%s' started (pid=%s)"
                                , $ttask[$tkey]['task']->get_task_id()
                                , $ttask[$tkey]["cmd"]
                                , $ttask[$tkey]['task']->get_pid()
                        )
                );
                $runningtask++;
              }
              else
              {
                $ttask[$tkey]["task"]->increment_crash_counter();

                @fclose($ttask[$tkey]["pipes"][1]);
                @fclose($ttask[$tkey]["pipes"][2]);
                @proc_close($ttask[$tkey]["process"]);
                $ttask[$tkey]["process"] = null;

                $this->log(
                        sprintf(
                                "Task %s '%s' failed to start %d times"
                                , $ttask[$tkey]["task"]->get_task_id()
                                , $ttask[$tkey]["cmd"]
                                , $ttask[$tkey]["task"]->get_crash_counter()
                        )
                );

                $ttask[$tkey]["task"]->increment_crash_counter();

                if ($ttask[$tkey]["task"]->get_crash_counter() > 5)
                {
                  $ttask[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
                }
                else
                {
                  $ttask[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
                }
              }
            }
            break;

          case 'started':
            $crashed = false;
            /**
             * If no process, the task is probably manually ran
             */
            if ($ttask[$tkey]["process"])
            {
              $ttask[$tkey]["killat"] = NULL;
              if (is_resource($ttask[$tkey]["process"]))
              {
                $proc_status = proc_get_status($ttask[$tkey]["process"]);
                if ($proc_status['running'])
                  $runningtask++;
                else
                  $crashed = true;
              }
              else
              {
                $crashed = true;
              }
            }

            if ($crashed === true && $ttask[$tkey]["task"]->get_status() === task_abstract::RETURNSTATUS_TORESTART)
            {
              $crashed = false;
            }
            if ($crashed)
            {
              $ttask[$tkey]["task"]->increment_crash_counter();

              @fclose($ttask[$tkey]["pipes"][1]);
              @fclose($ttask[$tkey]["pipes"][2]);
              @proc_close($ttask[$tkey]["process"]);
              $ttask[$tkey]["process"] = null;

              $this->log(
                      sprintf(
                              "Task %s crashed %d times"
                              , $ttask[$tkey]["task"]->get_task_id()
                              , $ttask[$tkey]["task"]->get_crash_counter()
                      )
              );


              $ttask[$tkey]["task"]->increment_crash_counter();

              if ($ttask[$tkey]["task"]->get_crash_counter() > 5)
              {
                $ttask[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
              }
              else
              {
                $ttask[$tkey]["task"]->set_status(task_abstract::STATUS_TOSTART);
              }
            }
            break;

          case 'tostop':
            if ($ttask[$tkey]["process"])
            {
              if ($ttask[$tkey]["killat"] === NULL)
                $ttask[$tkey]["killat"] = time() + self::TASKDELAYTOQUIT;
              if (($dt = $ttask[$tkey]["killat"] - time()) < 0)
              {
                $ppid = $ttask[$tkey]['task']->get_pid();
                $pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $ppid`);
                foreach ($pids as $pid)
                {
                  if (is_numeric($pid))
                  {
                    $this->log("Killing pid %d", $pid);
                    posix_kill($pid, 9);
                  }
                }

                $this->log(
                        sprintf(
                                "SIGKILL sent to task %s (pid=%s)"
                                , $ttask[$tkey]["task"]->get_task_id()
                                , $ttask[$tkey]["task"]->get_pid()
                        )
                );

                proc_terminate($ttask[$tkey]["process"], 9);
                @fclose($ttask[$tkey]["pipes"][1]);
                @fclose($ttask[$tkey]["pipes"][2]);
                proc_close($ttask[$tkey]["process"]);
                unlink($lockdir . 'task_' . $ttask[$tkey]['task']->get_task_id() . '.lock');

                $ttask[$tkey]["task"]->increment_crash_counter();
                $ttask[$tkey]["task"]->set_pid(null);
                $ttask[$tkey]["task"]->set_status(task_abstract::RETURNSTATUS_STOPPED);
              }
              else
              {
                $this->log(
                        sprintf(
                                "waiting task %s to quit (kill in %d seconds)"
                                , $ttask[$tkey]["task"]->get_task_id()
                                , $dt
                        )
                );
                $runningtask++;
              }
            }
            break;

          case 'stopped':
          case 'todelete':
            if ($ttask[$tkey]["process"])
            {
              @fclose($ttask[$tkey]["pipes"][1]);
              @fclose($ttask[$tkey]["pipes"][2]);
              @proc_close($ttask[$tkey]["process"]);

              $ttask[$tkey]["process"] = null;
            }
        }
      }

      $to_reopen = false;
      if ($conn->ping())
      {
        $conn->close();
        unset($conn);
        $to_reopen = true;
      }
      sleep($sleeptime);
      if ($to_reopen)
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
