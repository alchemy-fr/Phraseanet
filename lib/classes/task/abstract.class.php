<?php

abstract class task_abstract
{
    const LAUCHED_BY_BROWSER = 1;
    const LAUCHED_BY_COMMANDLINE = 2;
    const STATUS_TOSTOP = 'tostop';
    const STATUS_STARTED = 'started';
    const STATUS_TOSTART = 'tostart';
    const RETURNSTATUS_TORESTART = 'torestart';
    const RETURNSTATUS_STOPPED = 'stopped';
    const RETURNSTATUS_TODELETE = 'todelete';
    const RUNNER_MANUAL = 'manual';
    const RUNNER_SCHEDULER = 'scheduler';
    const STATE_OK = 'STATE_OK';
    const STATE_MAXMEGSREACHED = 'STATE_MAXMEGS';
    const STATE_MAXRECSDONE = 'STATE_MAXRECS';
    const STATE_FINISHED = 'STATE_FINISHED';
    const SIGNAL_SCHEDULER_DIED = 'SIGNAL_SCHEDULER_DIED';

    protected $suicidable = false;
    protected $launched_by = 0;

    /**
     * Number of records done
     *
     * @var <type>
     */
    protected $records_done = 0;

    /**
     * Maximum number of records before we restart the task
     *
     * @var <type>
     */
    protected $maxrecs;

    /**
     * Boolean switch to stop the task
     *
     * @var <type>
     */
    protected $running = false;

    /**
     * current number of loops done
     *
     * @var <type>
     */
    protected $loop = 0;

    /**
     * max number of loops before the task is restarted
     *
     * @var <type>
     */
    protected $maxloops = 5;

    /**
     * task status, either 'tostop' or 'started'
     *
     * @var <type>
     */
    protected $task_status = 0;

    /**
     * task state, either ok, maxmemory or maxrecords reached
     *
     * @var <type>
     */
    protected $current_state;

    /**
     * maximum memory allowed
     *
     * @var <type>
     */
    protected $maxmegs;

    /**
     * the return value for the scheduler
     *
     * @var <type>
     */
    protected $return_value;
    protected $runner;
    private $input;
    private $output;

    /**
     * delay between two loops
     *
     * @var <type>
     */
    protected $title;
    protected $settings;
    protected $crash_counter;
    protected $status;
    protected $active;
    protected $debug = false;

    public function get_status()
    {
        $conn = connection::getPDOConnection();
        $sql = 'SELECT status FROM task2 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->taskid));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ( ! $row)
            throw new Exception('Unknown task id');

        return $row['status'];
    }

    public function printInterfaceHEAD()
    {
        return false;
    }

    public function printInterfaceJS()
    {
        return false;
    }

    public function getGraphicForm()
    {
        return false;
    }

    public function set_status($status)
    {
        $av_status = array(
            self::STATUS_STARTED
            , self::STATUS_TOSTOP
            , self::RETURNSTATUS_STOPPED
            , self::RETURNSTATUS_TORESTART
            , self::STATUS_TOSTART
        );
        if ( ! in_array($status, $av_status))
            throw new Exception_InvalidArgument(sprintf('unknown status `%s`', $status));


        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET status = :status WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':status' => $status, ':taskid' => $this->get_task_id()));
        $stmt->closeCursor();
        $this->log(sprintf("task %d <- %s", $this->get_task_id(), $status));
        $this->task_status = $status;

        return $this->task_status;
    }

//  public function set_pid($pid)
//  {
//    $conn = connection::getPDOConnection();
//
//    $sql = 'UPDATE task2 SET pid = :pid WHERE task_id = :taskid';
//    $stmt = $conn->prepare($sql);
//    $stmt->execute(array(':pid' => $pid, ':taskid' => $this->get_task_id()));
//    $stmt->closeCursor();
//
//    return $this;
//  }
    // 'active' means 'auto-start when scheduler starts'
    public function set_active($boolean)
    {
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET active = :active WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':active' => ($boolean ? '1' : '0'), ':taskid' => $this->get_task_id()));
        $stmt->closeCursor();

        $this->active = ! ! $boolean;

        return $this;
    }

    public function set_title($title)
    {
        $title = strip_tags($title);
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET name = :title WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':title'  => $title, ':taskid' => $this->get_task_id()));
        $stmt->closeCursor();

        $this->title = $title;

        return $this;
    }

    public function set_settings($settings)
    {
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET settings = :settings WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':settings' => $settings, ':taskid'   => $this->get_task_id()));
        $stmt->closeCursor();

        $this->settings = $settings;

        $this->load_settings(simplexml_load_string($settings));

        return $this;
    }

    public function reset_crash_counter()
    {
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET crashed = 0 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->get_task_id()));
        $stmt->closeCursor();

        $this->crash_counter = 0;

        return $this;
    }

    public function get_crash_counter()
    {
        return $this->crash_counter;
    }

    public function increment_crash_counter()
    {
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET crashed = crashed + 1 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->get_task_id()));
        $stmt->closeCursor();

        return $this->crash_counter ++;
    }

    public function get_settings()
    {
        return $this->settings;
    }

    // 'active' means 'auto-start when scheduler starts'
    public function is_active()
    {
        return $this->active;
    }

    public function get_completed_percentage()
    {
        return $this->completed_percentage;
    }
    protected $period = 10;
    protected $taskid = NULL;
    protected $system = '';  // "DARWIN", "WINDOWS" , "LINUX"...
    protected $argt = array(
        "--help" => array("set"    => false, "values" => array(), "usage" => " (no help available)")
    );

    abstract public function getName();

    abstract public function help();

    function __construct($taskid)
    {
        $this->taskid = $taskid;

        phrasea::use_i18n(Session_Handler::get_locale());

        $this->system = system_server::get_platform();

        $this->launched_by = array_key_exists("REQUEST_URI", $_SERVER) ? self::LAUCHED_BY_BROWSER : self::LAUCHED_BY_COMMANDLINE;
        if ($this->system != "DARWIN" && $this->system != "WINDOWS" && $this->system != "LINUX") {
            if ($this->launched_by == self::LAUCHED_BY_COMMANDLINE) {
//        printf("Desole, ce programme ne fonctionne pas sous '" . $this->system . "'.\n");
                flush();
            }
            exit(-1);
        } else {
            if ($this->launched_by == self::LAUCHED_BY_COMMANDLINE) {
                flush();
            }
        }

        try {
            $conn = connection::getPDOConnection();
        } catch (Exception $e) {
            $this->log($e->getMessage());
            $this->log(("Warning : abox connection lost, restarting in 10 min."));

            for ($t = 60 * 10; $this->running && $t > 0; $t -- ) // DON'T do sleep(600) because it prevents ticks !
                sleep(1);

            $this->running = false;

            return('');
        }
        $sql = 'SELECT crashed, pid, status, active, settings, name, completed, runner
              FROM task2 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->get_task_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ( ! $row)
            throw new Exception('Unknown task id');
        $this->title = $row['name'];
        $this->crash_counter = (int) $row['crashed'];
        $this->task_status = $row['status'];
        $this->active = ! ! $row['active'];
        $this->settings = $row['settings'];
        $this->runner = $row['runner'];
        $this->completed_percentage = (int) $row['completed'];
        $this->load_settings(simplexml_load_string($row['settings']));

        return $this;
    }

    public function get_runner()
    {
        return $this->runner;
    }

    public function set_runner($runner)
    {
        $this->runner = $runner;

        $conn = connection::getPDOConnection();
        $sql = 'UPDATE task2 SET runner = :runner WHERE task_id = :taskid';

        $params = array(
            ':taskid' => $this->get_task_id()
            , ':runner' => $this->runner
        );

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_title()
    {
        return $this->title;
    }

    public function delete()
    {
        if ( ! $this->get_pid()) { // do not delete a running task
            $conn = connection::getPDOConnection();
            $registry = registry::get_instance();
            $sql = "DELETE FROM task2 WHERE task_id = :task_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':task_id' => $this->get_task_id()));
            $stmt->closeCursor();

            $lock_file = $registry->get('GV_RootPath') . 'tmp/locks/task_' . $this->get_task_id() . '.lock';
            @unlink($lock_file);
        }
    }

    protected function check_memory_usage()
    {
        $current_memory = memory_get_usage();
        if ($current_memory >> 20 >= $this->maxmegs) {
            $this->log(sprintf(
                    "Max memory (%s M) reached (current is %s M)"
                    , $this->maxmegs
                    , $current_memory
                ));
            $this->current_state = self::STATE_MAXMEGSREACHED;
        }

        return $this;
    }

    protected function check_records_done()
    {
        if ($this->records_done >= (int) ($this->maxrecs)) {
            $this->current_state = self::STATE_MAXRECSDONE;
        }

        return $this;
    }

    public function set_last_exec_time()
    {
        $conn = connection::getPDOConnection();
        $sql = 'UPDATE task2 SET last_exec_time=NOW() WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->get_task_id()));
        $stmt->closeCursor();

        return $this;
    }

    public function get_last_exec_time()
    {
        $conn = connection::getPDOConnection();
        $sql = 'SELECT last_exec_time FROM task2 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->get_task_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return isset($row['last_exec_time']) ? $row['last_exec_time'] : '';
    }

    public function get_pid()
    {
        $pid = NULL;

        $taskid = $this->get_task_id();

        $registry = registry::get_instance();
        system_file::mkdir($lockdir = $registry->get('GV_RootPath') . 'tmp/locks/');

        if (($fd = fopen(($lockfile = ($lockdir . 'task_' . $taskid . '.lock')), 'a+'))) {
// ************************************************
// file_put_contents("/tmp/scheduler2.log", sprintf("%s [%d] : fopen(%s) \n", __FILE__, __LINE__, $lockfile), FILE_APPEND);
            if (flock($fd, LOCK_EX | LOCK_NB) === FALSE) {
                // already locked ? : task running
                $pid = fgets($fd);
// ************************************************
// file_put_contents("/tmp/scheduler2.log", sprintf("%s [%d] : can't flock() : pid=%s \n", __FILE__, __LINE__, $pid), FILE_APPEND);
            } else {
                // can lock : not running
// ************************************************
// file_put_contents("/tmp/scheduler2.log", sprintf("%s [%d] : NOT RUNNING can flock() : pid=%s \n", __FILE__, __LINE__, file_get_contents($lockfile)), FILE_APPEND);
                flock($fd, LOCK_UN);
            }
            fclose($fd);
        }

        return $pid;
    }
    /*
      public function is_running()
      {
      $retval = false;
      $registry = registry::get_instance();
      $lockdir = $registry->get('GV_RootPath') . 'tmp/locks/';
      $tasklock = fopen($lockfile = ($lockdir . '/task_' . $this->get_task_id() . '.lock'), 'a+');

      if (flock($tasklock, LOCK_SH | LOCK_NB) != true)
      {
      $retval = true;
      }
      else
      {
      ftruncate($tasklock, 0);
      flock($tasklock, LOCK_UN | LOCK_NB);
      fclose($tasklock);
      unlink($lockfile);
      }

      return $retval;
      }
     */

    protected function check_current_state()
    {
        switch ($this->current_state) {
            case self::STATE_MAXMEGSREACHED:
            case self::STATE_MAXRECSDONE:
            default:
                if ($this->get_runner() == self::RUNNER_SCHEDULER) {
                    $this->task_status = self::STATUS_TOSTOP;
                    $this->return_value = self::RETURNSTATUS_TORESTART;
                }
                break;
            case self::STATE_FINISHED:
                $this->task_status = self::STATUS_TOSTOP;
                if ($this->suicidable === true) {
                    $this->return_value = self::RETURNSTATUS_TODELETE;
                    $this->log('will hang myself');
                } else {
                    $this->return_value = self::RETURNSTATUS_STOPPED;
                }
                break;
            case self::STATE_OK:

                break;
        }
        $this->apply_task_status();

        return $this;
    }

    protected function check_task_status()
    {
        try {
            $conn = connection::getPDOConnection();
            $sql = "SELECT status FROM task2 WHERE task_id = :taskid";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':taskid' => $this->get_task_id()));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            if ( ! $row || $row['status'] == 'tostop') {
                $this->task_status = self::STATUS_TOSTOP;
                $this->return_value = self::RETURNSTATUS_STOPPED;
            }
        } catch (Exception $e) {
            $this->return_value = self::RETURNSTATUS_STOPPED;
            $this->task_status = self::STATUS_TOSTOP;
        }
        $this->apply_task_status();

        return $this;
    }

    public function set_running($stat)
    {
        $this->running = $stat;
    }

    protected function pause($when_started = 0)
    {
        $this->log($this->records_done . ' records done');
        if ($this->running) {// && $this->records_done == 0)
            $when_started = time() - $when_started;
            if ($when_started < $this->period) {
                $conn = connection::getPDOConnection();
                $conn->close();
                unset($conn);
                for ($t = $this->period - $when_started; $this->running && $t > 0; $t -- ) // DON'T do sleep($this->period - $when_started) because it prevents ticks !
                    sleep(1);
            }
        }
    }

    final public function run($runner, $input = null, $output = null)
    {
        $this->input = $input;
        $this->output = $output;

// ************************************************
// file_put_contents("/tmp/scheduler2.log", sprintf("%s [%d] : LAUNCHING : tid=%s \n", __FILE__, __LINE__, $this->get_task_id()), FILE_APPEND);
        $taskid = $this->get_task_id();
        $conn = connection::getPDOConnection();

        $registry = registry::get_instance();
        system_file::mkdir($lockdir = $registry->get('GV_RootPath') . 'tmp/locks/');
        $locker = true;
        $tasklock = fopen(($lockfile = ($lockdir . 'task_' . $taskid . '.lock')), 'a+');
        if (flock($tasklock, LOCK_EX | LOCK_NB, $locker) === FALSE) {
// ************************************************
// file_put_contents("/tmp/scheduler2.log", sprintf("%s [%d] : LAUNCH OPENED AND CANT LOCK : pid=%s \n", __FILE__, __LINE__, getmypid()), FILE_APPEND);
            $this->log("runtask::ERROR : task already running.");
            fclose($tasklock);

            return;
        } else {
            ftruncate($tasklock, 0);
            fwrite($tasklock, '' . getmypid());
            fflush($tasklock);
// ************************************************
// file_put_contents("/tmp/scheduler2.log", sprintf("%s [%d] : LAUNCH OPENED AND LOCKED : pid=%s \n", __FILE__, __LINE__, getmypid()), FILE_APPEND);
        }

        $this->set_runner($runner);
//    $this->set_pid(getmypid());
        $this->set_status(self::STATUS_STARTED);

        $this->running = true;

        $this->run2();

        flock($tasklock, LOCK_UN | LOCK_NB);
        ftruncate($tasklock, 0);
        fclose($tasklock);
        @unlink($lockfile);

        if ($this->return_value == self::RETURNSTATUS_TODELETE)
            $this->delete();
        else
            $this->set_status($this->return_value);

        return $this;
    }

    abstract protected function run2();

    protected function load_settings(SimpleXMLElement $sx_task_settings)
    {
        $this->period = max(10, min(3600, (int) $sx_task_settings->period));
        $this->maxrecs = max(10, min(1000, (int) $sx_task_settings->maxrecs));
        $this->maxmegs = max(16, min(512, (int) $sx_task_settings->maxmegs));
        $this->record_buffer_size = max(1, min(100, (int) $sx_task_settings->flush));

        return $this;
    }

    protected function increment_loops()
    {
        if ($this->get_runner() == self::RUNNER_SCHEDULER && $this->loop > $this->maxloops) {
            $this->log(sprintf(('%d loops done, restarting'), $this->loop));
            $this->task_status = self::STATUS_TOSTOP;
            $this->return_value = self::RETURNSTATUS_TORESTART;
        }
        $this->apply_task_status();
        $this->loop ++;

        return $this;
    }

    public function apply_task_status()
    {
        if ($this->task_status == self::STATUS_TOSTOP) {
            $this->running = false;
        }

        return $this;
    }

    function traceRam($msg = '')
    {
        static $lastt = null;
        $t = explode(' ', ($ut = microtime()));
        if ($lastt === null)
            $lastt = $t;
        $dt = ($t[0] - $lastt[0]) + ($t[1] - $lastt[1]);

        $m = memory_get_usage() >> 10;
        $d = debug_backtrace(false);

        $lastt = $t;
        echo "\n" . memory_get_usage() . " -- " . memory_get_usage(true) . "\n";
        // print($s);
    }

    public function log($message)
    {
        $registry = registry::get_instance();
        $logdir = $registry->get('GV_RootPath') . 'logs/';

        logs::rotate($logdir . 'task_l_' . $this->taskid . '.log');
        logs::rotate($logdir . 'task_o_' . $this->taskid . '.log');
        logs::rotate($logdir . 'task_e_' . $this->taskid . '.log');

        $date_obj = new DateTime();
        $message = sprintf("%s\t%s", $date_obj->format(DATE_ATOM), $message);

        if ($this->output) {
            $this->output->writeln($message);
        }
        if ($this->input && ! ($this->input->getOption('nolog'))) {
            file_put_contents($logdir . 'task_l_' . $this->taskid . '.log', $message . "\n", FILE_APPEND);
        }

        return $this;
    }

    public static function interfaceAvailable()
    {
        return true;
    }

    /**
     *
     * @param appbox $appbox
     * @param type $class_name
     * @param type $settings
     * @return task_abstract
     */
    public static function create(appbox $appbox, $class_name, $settings = null)
    {
        if ( ! class_exists($class_name))
            throw new Exception('Unknown task class');

        $sql = 'INSERT INTO task2
                    (task_id, usr_id_owner, status, crashed, active,
                      name, last_exec_time, class, settings)
                    VALUES
                    (null, 0, "stopped", 0, :active,
                      :name, "0000/00/00 00:00:00", :class, :settings)';


        if ($settings && ! DOMDocument::loadXML($settings))
            throw new Exception('settings invalide');
        elseif ( ! $settings)
            $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n</tasksettings>";

        $params = array(
            ':active'   => 1
            , ':name'     => $class_name::getName()
            , ':class'    => $class_name
            , ':settings' => $settings
        );
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $tid = $appbox->get_connection()->lastInsertId();

        return new $class_name($tid);
    }

    public function getUsage()
    {
        global $argc, $argv;
        $t = "usage: " . $argv[0] . " [options]\noptions:\n";
        foreach ($this->argt as $n => $v)
            $t .= "\t" . $n . $v["usage"] . "\n";

        return($t);
    }

    public function get_argt()
    {
        return $this->argt;
    }

    public function get_task_id()
    {
        return $this->taskid;
    }
    protected $completed_percentage;

    function setProgress($done, $todo)
    {
        $p = ($todo > 0) ? ((100 * $done) / $todo) : -1;

        try {
            $conn = connection::getPDOConnection();
            $sql = 'UPDATE task2 SET completed = :p WHERE task_id = :taskid';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':p'      => $p, ':taskid' => $this->get_task_id()));
            $stmt->closeCursor();
            $this->completed_percentage = $p;
        } catch (Exception $e) {

        }

        return $this;
    }
}
