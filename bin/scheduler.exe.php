<?php
require_once dirname(__FILE__) . "/../lib/bootstrap_task.php";
define('TASKDELAYTOQUIT', 20);  // allow 20 sec for never-ending tasks before sending SIGKILL
?>
#!/usr/bin/php
<?php
require(GV_RootPath . "lib/getargs.php");  // le parser d'arguments de la ligne de commande


$system = p4utils::getSystem();

if ($system != "DARWIN" && $system != "WINDOWS" && $system != "LINUX")
{
  $msg = "Desole, ce programme ne fonctionne pas sous '" . $system;
  my_syslog(LOG_ERR, $msg);
  die();
}


p4::fullmkdir($lockdir = GV_RootPath . 'tmp/locks/');

// try to lock one instance of the scheduler
for ($try = 0; true; $try++)
{
  $schedlock = fopen(($lockfile = ($lockdir . 'scheduler.lock')), 'a+');
  if (flock($schedlock, LOCK_EX | LOCK_NB) != true)
  {
    $msg = sprintf("failed to lock '%s' (try=%s/4)", $lockfile, $try);
    my_syslog(LOG_ERR, $msg);
    if ($try == 4)
    {
      $msg = sprintf("scheduler already running.");
      my_syslog(LOG_ERR, $msg);
      fclose($schedlock);
      exit(-1);
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

function my_syslog($level, $msg)
{
  global $argt;
  if (!$argt["--noecho"]["set"])
  {
    $msg = sprintf("%s : %s" . PHP_EOL, date("r"), $msg);
    printf("%s", $msg);
  }
}

$argt = array(
    "--help" => array("set" => false, "values" => array(), "usage" => " : this help"),
    "--noecho" => array("set" => false, "values" => array(), "usage" => " : no scheduler console output"),
    "--nolog" => array("set" => false, "values" => array(), "usage" => " : no scheduler logfile"),
    "--notasklog" => array("set" => false, "values" => array(), "usage" => " : no task logfiles")
);

$logdir = p4string::addEndSlash(GV_RootPath . 'logs');

if (is_dir($logdir))
{
  $logdir = p4string::addEndSlash($logdir);
}
else
{
  $logdir = null;
}

if (!parse_cmdargs($argt, $err) || $argt["--help"]["set"])
{
  print($err);
  print_usage($argt);
  flush();
  die();
}

$conn = new connection('appbox');

my_syslog(LOG_INFO, sprintf("Scheduler2 started on %s, cwd='%s'", $system, getcwd()));

$ttask = array();

$sleeptime = 3; // le sheduler tourne toutes les 3 secondes

$prompt = "PIV Scheduler-2: ";
$help = "Commands :\r\n 'help' : this help\r\n 'shutdown' : stop scheduler2\r\n";

set_time_limit(0);
session_write_close();
ignore_user_abort(true);

$sql = "UPDATE sitepreff SET schedstatus='started', schedpid='" . $conn->escape_string(getmypid()) . "'";
$conn->query($sql);


// flag tasks to start (actives with no lockfile)
$tlist = '';
$sql = "SELECT task_id FROM task2 WHERE active>0";
if ($rs = $conn->query($sql))
{
  // on stocke dans un tableau car on va faire des updates et on ne va pas les faire pendant la lecture du rs
  while ($row = $conn->fetch_assoc($rs))
  {
    $tid = $row['task_id'];
    // si elle ne tourne pas, on va demander le lancement
    if (($fp = fopen($lockdir . 'task_' . $tid . '.lock', 'w')))
    {
      if (flock($fp, LOCK_EX | LOCK_NB) == true)
      {
        $tlist .= ( $tlist ? ',' : '') . $tid;
      }
      fclose($fp);
    }
  }
  $conn->free_result($rs);
}
if ($tlist)
{
  $sql = "UPDATE task2 SET crashed=0, status='tostart' WHERE task_id IN(" . $tlist . ")";
  $conn->query($sql);
}


$schedstatus = 'started';
$runningtask = 0;
$connwaslost = false;

$last_log_check = array();

while ($schedstatus == 'started' || $runningtask > 0)
{
  while (!$conn || !$conn->isok() || !$conn->ping())
  {
    unset($conn);
    if (!$connwaslost)
    {
      $msg = sprintf(("Warning : abox connection lost, restarting in 10 min."));
      my_syslog(LOG_INFO, $msg);
    }
    sleep(60 * 10);
    $conn = new connection('appbox');
    $connwaslost = true;
  }
  if ($connwaslost)
  {
    $msg = sprintf(("abox connection restored"));
    my_syslog(LOG_INFO, $msg);
    $sql = 'UPDATE task SET crashed=0';
    $conn->query($sql);

    $connwaslost = false;
  }

  $schedstatus = '';
  $sql = "SELECT schedstatus FROM sitepreff";
  if ($rs = $conn->query($sql))
  {
    if ($row = $conn->fetch_assoc($rs))
      $schedstatus = $row["schedstatus"];
    $conn->free_result($rs);
  }

  if ($schedstatus == 'tostop')
  {
    $sql = 'UPDATE sitepreff SET schedstatus=\'stopping\'';
    $conn->query($sql);

    // if scheduler is stopped, stop the tasks
    $sql = 'UPDATE task2 SET status=\'tostop\' WHERE status!="stopped" and status!="manual"';
    $conn->query($sql);

    $msg = sprintf("schedstatus == 'stopping', waiting tasks to end");
    my_syslog(LOG_INFO, $msg);
  }

  logs::rotate($logdir . "scheduler.log");
  logs::rotate($logdir . "scheduler.error.log");


  foreach ($ttask as $tkey => $tv) 
  {
    $ttask[$tkey]["todel"] = true;
  }
  
  $sql = "SELECT * FROM task2"; // WHERE active>0 AND crashed<5";

  if ($rs = $conn->query($sql))
  {
    while ($row = $conn->fetch_assoc($rs))
    {
      $tkey = "t_" . $row["task_id"];

      logs::rotate($logdir . "task_$tkey.log");
      logs::rotate($logdir . "task_$tkey.error.log");

      if (!isset($ttask[$tkey]))
      {
        $phpcli = GV_cli;

        switch ($system)
        {
          case "DARWIN":
            $cmd = $phpcli . ' -f ' . "schedtask.php -- --taskid=" . $row["task_id"];
            break;
          case "LINUX":
            $cmd = $phpcli . ' -f ' . GV_RootPath . "bin/schedtask.php -- --taskid=" . $row["task_id"];
            break;
          case "WINDOWS":
            $cmd = $phpcli . ' -f ' . "schedtask.php -- --taskid=" . $row["task_id"];
            break;
        }

        $ttask[$tkey] = array(
            "active" => (int) ($row['active']),
            "crashed" => (int) ($row['crashed']),
            "status" => $row['status'],
            "process" => null,
            "tid" => $row["task_id"],
            "cmd" => $cmd,
            "killat" => NULL,
            //		"todel"=>false,
            //		"descriptors"=>null,
            "pipes" => null
        );
        $msg = sprintf("new Task %s, status=%s", $ttask[$tkey]["tid"], $row['status']);
        my_syslog(LOG_INFO, $msg);
      }
      else
      {
        //	$ttask[$tkey]["todel"] = false;
        if ($ttask[$tkey]["status"] != $row['status'])
        {
          $msg = sprintf("Task %s, oldstatus=%s, newstatus=%s", $ttask[$tkey]["tid"], $ttask[$tkey]["status"], $row['status']);
          my_syslog(LOG_INFO, $msg);
          $ttask[$tkey]["status"] = $row['status'];
        }

        $ttask[$tkey]["crashed"] = (int) ($row['crashed']);
        $ttask[$tkey]["active"] = $row['active'];
      }
      $ttask[$tkey]["todel"] = false;
    }
    $conn->free_result($rs);
  }

  foreach ($ttask as $tkey => $tv)
  {
    if ($tv["todel"])
    {
      $msg = sprintf("Task %s deleted", $ttask[$tkey]["tid"]);
      my_syslog(LOG_INFO, $msg);
      unset($ttask[$tkey]);
    }
    if ($ttask[$tkey]["crashed"] >= 5)
    {
      $ttask[$tkey]["active"] = 0;
    }
  }


  // on lance les t�ches qui n'ont pas encore de process (inserted ou activ�es)
  // $taskcrashed = array();
  $runningtask = 0;
  foreach ($ttask as $tkey => $tv)
  {
    switch ($ttask[$tkey]['status'])
    {
      case 'torestart':
        @fclose($ttask[$tkey]["pipes"][1]);
        @fclose($ttask[$tkey]["pipes"][2]);
        @proc_close($ttask[$tkey]["process"]);

        $ttask[$tkey]["process"] = null;
        if($schedstatus == 'started')
        {
          $sql = 'UPDATE task2 SET status=\'tostart\' WHERE task_id="' . $conn->escape_string($ttask[$tkey]["tid"]) . '"';
          $conn->query($sql);
        }
        break;
      case 'tostart':
        $ttask[$tkey]["killat"] = NULL;
        if ($schedstatus == 'started' && !$ttask[$tkey]["process"])
        {
          // on lui adjoint des descriptors stdio
          $descriptors = array(1 => array("pipe", "w"), 2 => array("pipe", "w"));

          if ((!$argt["--notasklog"]["set"]) && $logdir)
          {
            $descriptors[1] = array("file", $logdir . "task_$tkey.log", "a+");
            $descriptors[2] = array("file", $logdir . "task_$tkey.error.log", "a+");
          }

          $cmd = $ttask[$tkey]["cmd"];

          //		$pipes = array();

          $ttask[$tkey]["process"] = proc_open($cmd, $descriptors, $ttask[$tkey]["pipes"], GV_RootPath . "bin/", null, array('bypass_shell' => true));
          //		$ttask[$tkey]["pipes"] = $pipes;

          $ttask[$tkey]['pid'] = NULL;
          if (is_resource($ttask[$tkey]["process"]))
          {
            //$msg = sprintf("PROC2: '%s', %s", $cmd, print_r($ttask[$tkey]["process"], true));
            //my_syslog(LOG_INFO, $msg);
            $proc_status = proc_get_status($ttask[$tkey]["process"]);
            if ($proc_status['running'])
              $ttask[$tkey]['pid'] = $proc_status['pid'];
          }
          if ($ttask[$tkey]['pid'] !== NULL)
          {
            $sql = 'UPDATE task2 SET status=\'started\' WHERE task_id="' . $conn->escape_string($ttask[$tkey]['tid']) . '"';
            $conn->query($sql);
            $msg = sprintf("Task %s '%s' started (pid=%s)", $ttask[$tkey]["tid"], $cmd, $ttask[$tkey]['pid']);
            my_syslog(LOG_INFO, $msg);
            $runningtask++;
          }
          else
          {
            $ttask[$tkey]["crashed"]++;

            @fclose($ttask[$tkey]["pipes"][1]);
            @fclose($ttask[$tkey]["pipes"][2]);
            @proc_close($ttask[$tkey]["process"]);
            $ttask[$tkey]["process"] = null;

            $msg = sprintf("Task %s '%s' failed to start %d times", $ttask[$tkey]["tid"], $cmd, $ttask[$tkey]["crashed"]);
            my_syslog(LOG_INFO, $msg);
            if ($ttask[$tkey]["crashed"] >= 5)
              $sql = "UPDATE task2 SET status='stopped', pid=0, crashed=crashed+1 WHERE task_id='" . $conn->escape_string($ttask[$tkey]['tid']) . "'";
            else
              $sql = "UPDATE task2 SET status='tostart', pid=0, crashed=crashed+1 WHERE task_id='" . $conn->escape_string($ttask[$tkey]['tid']) . "'";
            $conn->query($sql);
          }
        }
        else
        {
          
        }
        break;

      case 'started':
        $crashed = false;
        if ($ttask[$tkey]["process"]) // if no process, probably ran by hand (runtask), so don't worry
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
        if($crashed)
        {
          //on verifie que c'est pas passé a restart en attendant
          $sql = "SELECT status FROM task2 WHERE task_id='".$conn->escape_string($ttask[$tkey]['tid'])."'";
          if($rs = $conn->query($sql))
          {
            if($row = $conn->fetch_assoc($rs))
            {
              if($row['status'] == 'torestart')
                $crashed = false;
            }
            $conn->free_result($rs);
          }
        }
        if ($crashed)
        {
          $ttask[$tkey]["crashed"]++;

          @fclose($ttask[$tkey]["pipes"][1]);
          @fclose($ttask[$tkey]["pipes"][2]);
          @proc_close($ttask[$tkey]["process"]);
          $ttask[$tkey]["process"] = null;

          $msg = sprintf("Task %s crashed %d times", $ttask[$tkey]["tid"], $ttask[$tkey]["crashed"]);
          my_syslog(LOG_INFO, $msg);
          if ($ttask[$tkey]["crashed"] >= 5)
            $sql = "UPDATE task2 SET status='stopped', pid=0, crashed=crashed+1 WHERE task_id='" . $conn->escape_string($ttask[$tkey]['tid']) . "'";
          else
            $sql = "UPDATE task2 SET status='tostart', pid=0, crashed=crashed+1 WHERE task_id='" . $conn->escape_string($ttask[$tkey]['tid']) . "'";
          $conn->query($sql);
        }
        break;

      case 'tostop':
        if ($ttask[$tkey]["process"])  // if no process (ran by 'runtask' ?)... can't kill...
        {
          if ($ttask[$tkey]["killat"] === NULL)
            $ttask[$tkey]["killat"] = time() + TASKDELAYTOQUIT;
          if (($dt = $ttask[$tkey]["killat"] - time()) < 0)
          {
            // task is still alive after delay so kill it
            $ppid = $ttask[$tkey]['pid'];
            my_syslog(LOG_INFO, "killing children of pid=$ppid");
            $pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $ppid`);
            foreach ($pids as $pid)
            {
              if (is_numeric($pid))
              {
                $msg = "Killing pid=$pid";
                posix_kill($pid, 9); //9 is the SIGKILL signal
                my_syslog(LOG_INFO, $msg);
              }
            }

            $msg = sprintf("SIGKILL sent to task %s (pid=%s)", $ttask[$tkey]["tid"], $ttask[$tkey]['pid']);
            my_syslog(LOG_INFO, $msg);
            proc_terminate($ttask[$tkey]["process"], 9);
            @fclose($ttask[$tkey]["pipes"][1]);
            @fclose($ttask[$tkey]["pipes"][2]);
            proc_close($ttask[$tkey]["process"]);
            unlink($lockdir . 'task_' . $ttask[$tkey]['tid'] . '.lock');

            $sql = "UPDATE task2 SET status='stopped', pid=0, crashed=crashed+1 WHERE task_id='" . $conn->escape_string($ttask[$tkey]['tid']) . "'";
            $conn->query($sql);

            $ttask[$tkey]['pid'] = NULL;
          }
          else
          {
            $msg = sprintf("waiting task %s to quit (kill in %d seconds)", $ttask[$tkey]["tid"], $dt);
            my_syslog(LOG_INFO, $msg);
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
// $msg = sprintf("runningtask = %d", $runningtask);
// my_syslog(LOG_INFO, $msg);

  $to_reopen = false;
  if ($conn && $conn->isok() && $conn->ping())
  {
    $conn->close();
    unset($conn);
    $to_reopen = true;
  }
  sleep($sleeptime);
  if ($to_reopen)
  {
    $conn = new connection('appbox');
  }
}

$sql = "UPDATE sitepreff SET schedstatus='stopped', schedpid='0'";
$conn->query($sql);


// ============================================================================
// on quitte le scheduler
$msg = "Scheduler2 is quitting.";
my_syslog(LOG_INFO, $msg);

ftruncate($schedlock, 0);
fclose($schedlock);

/*
  if($sock)
  socket_close($sock);
  $sock = NULL;
 */
$msg = "Scheduler2 has quit.\n";
my_syslog(LOG_INFO, $msg);
?>
