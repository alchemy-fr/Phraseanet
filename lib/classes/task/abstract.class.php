<?php

abstract class task_abstract
{
    const LAUCHED_BY_BROWSER = 1;
    const LAUCHED_BY_COMMANDLINE = 2;
    const STATE_TOSTOP = 'tostop';
    const STATE_STARTED = 'started';
    const STATE_TOSTART = 'tostart';
    const STATE_TORESTART = 'torestart';
    const STATE_STOPPED = 'stopped';
    const STATE_TODELETE = 'todelete';
    const RUNNER_MANUAL = 'manual';
    const RUNNER_SCHEDULER = 'scheduler';
    const STATE_OK = 'STATE_OK';
    const STATE_MAXMEGSREACHED = 'STATE_MAXMEGS';
    const STATE_MAXRECSDONE = 'STATE_MAXRECS';
    const STATE_FINISHED = 'STATE_FINISHED';
    const SIGNAL_SCHEDULER_DIED = 'SIGNAL_SCHEDULER_DIED';
    const ERR_ALREADY_RUNNING = 114;   // aka EALREADY (Operation already in progress)

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
    protected $completed_percentage;
    protected $period = 60;
    protected $taskid = NULL;
    protected $system = '';  // "DARWIN", "WINDOWS" , "LINUX"...
    protected $argt = array(
        "--help" => array("set"    => false, "values" => array(), "usage" => " (no help available)")
    );

    public function getState()
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

    public function setState($status)
    {
        $av_status = array(
            self::STATE_STARTED
            , self::STATE_TOSTOP
            , self::STATE_STOPPED
            , self::STATE_TORESTART
            , self::STATE_TOSTART
            , self::STATE_TODELETE
        );

        if ( ! in_array($status, $av_status)) {
            throw new Exception_InvalidArgument(sprintf('unknown status `%s`', $status));
        }

        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET status = :status WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':status' => $status, ':taskid' => $this->getID()));
        $stmt->closeCursor();
        $this->log(sprintf("task %d <- %s", $this->getID(), $status));
    }

    // 'active' means 'auto-start when scheduler starts'
    public function setActive($boolean)
    {
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET active = :active WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':active' => ($boolean ? '1' : '0'), ':taskid' => $this->getID()));
        $stmt->closeCursor();

        $this->active = ! ! $boolean;

        return $this;
    }

    public function setTitle($title)
    {
        $title = strip_tags($title);
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET name = :title WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':title'  => $title, ':taskid' => $this->getID()));
        $stmt->closeCursor();

        $this->title = $title;

        return $this;
    }

    public function setSettings($settings)
    {
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET settings = :settings WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':settings' => $settings, ':taskid'   => $this->getID()));
        $stmt->closeCursor();

        $this->settings = $settings;

        $this->loadSettings(simplexml_load_string($settings));
    }

    public function resetCrashCounter()
    {
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET crashed = 0 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->getID()));
        $stmt->closeCursor();

        $this->crash_counter = 0;

        return $this;
    }

    public function getCrashCounter()
    {
        return $this->crash_counter;
    }

    public function incrementCrashCounter()
    {
        $conn = connection::getPDOConnection();

        $sql = 'UPDATE task2 SET crashed = crashed + 1 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->getID()));
        $stmt->closeCursor();

        return $this->crash_counter ++;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    // 'active' means 'auto-start when scheduler starts'
    public function isActive()
    {
        return $this->active;
    }

    public function getCompletedPercentage()
    {
        return $this->completed_percentage;
    }

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
        $stmt->execute(array(':taskid' => $this->getID()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ( ! $row)
            throw new Exception('Unknown task id');
        $this->title = $row['name'];
        $this->crash_counter = (int) $row['crashed'];
        $this->active = ! ! $row['active'];
        $this->settings = $row['settings'];
        $this->runner = $row['runner'];
        $this->completed_percentage = (int) $row['completed'];
        $this->loadSettings(simplexml_load_string($row['settings']));
    }

    public function getRunner()
    {
        return $this->runner;
    }

    public function setRunner($runner)
    {
        $this->runner = $runner;

        $conn = connection::getPDOConnection();
        $sql = 'UPDATE task2 SET runner = :runner WHERE task_id = :taskid';

        $params = array(
            ':taskid' => $this->getID()
            , ':runner' => $this->runner
        );

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function delete()
    {
        if ( ! $this->getPID()) { // do not delete a running task
            $conn = connection::getPDOConnection();
            $registry = registry::get_instance();
            $sql = "DELETE FROM task2 WHERE task_id = :task_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':task_id' => $this->getID()));
            $stmt->closeCursor();

            $lock_file = $registry->get('GV_RootPath') . 'tmp/locks/task_' . $this->getID() . '.lock';
            @unlink($lock_file);
        }
    }

    public function setLastExecTime()
    {
        $conn = connection::getPDOConnection();
        $sql = 'UPDATE task2 SET last_exec_time=NOW() WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->getID()));
        $stmt->closeCursor();
    }

    /**
     * Return the last time the task was executed
     * @return string
     */
    public function getLastExecTime()
    {
        $conn = connection::getPDOConnection();
        $sql = 'SELECT last_exec_time FROM task2 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->getID()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return isset($row['last_exec_time']) ? $row['last_exec_time'] : '';
    }

    public function getPID()
    {
        $pid = NULL;
        $taskid = $this->getID();

        $registry = registry::get_instance();
        system_file::mkdir($lockdir = $registry->get('GV_RootPath') . 'tmp/locks/');

        if (($fd = fopen(($lockfile = ($lockdir . 'task_' . $taskid . '.lock')), 'a+'))) {
            if (flock($fd, LOCK_EX | LOCK_NB) === FALSE) {
                // already locked ? : task running
                $pid = fgets($fd);
            } else {
                // can lock : not running
                flock($fd, LOCK_UN);
            }
            fclose($fd);
        }

        return $pid;
    }

    public function setRunning($stat)
    {
        $this->running = $stat;
    }

    protected function pause($when_started = 0)
    {
        $this->log($this->records_done . ' records done');
        if ($this->running) {// && $this->records_done == 0)
            $when_started = time() - $when_started;
            if ($when_started < $this->period) {
                for ($t = $this->period - $when_started; $this->running && $t > 0; $t -- ) { // DON'T do sleep($this->period - $when_started) because it prevents ticks !
                    $s = $this->getState();
                    if ($s == self::STATE_TOSTOP) {
                        $this->setState(self::STATE_STOPPED);
                        $this->running = FALSE;
                    } else {
                        sleep(1);
                    }
                }
            }
        }
    }

    final public function run($runner, $input = null, $output = null)
    {
        $this->input = $input;
        $this->output = $output;

        $taskid = $this->getID();
        $conn = connection::getPDOConnection();

        $registry = registry::get_instance();
        system_file::mkdir($lockdir = $registry->get('GV_RootPath') . 'tmp/locks/');
        $locker = true;
        $tasklock = fopen(($lockfile = ($lockdir . 'task_' . $taskid . '.lock')), 'a+');

        if (flock($tasklock, LOCK_EX | LOCK_NB, $locker) === FALSE) {
            $this->log("runtask::ERROR : task already running.");
            fclose($tasklock);

            throw new Exception('task already running.', self::ERR_ALREADY_RUNNING);
            return;
        }

        // here we run the task
        ftruncate($tasklock, 0);
        fwrite($tasklock, '' . getmypid());
        fflush($tasklock);

        // for windows : unlock then lock shared to allow OTHER processes to read the file
        // too bad : no critical section nor atomicity
        flock($tasklock, LOCK_UN);
        flock($tasklock, LOCK_SH);

        $this->setRunner($runner);
        $this->setState(self::STATE_STARTED);

        // run the real code of the task -into the task's class- (may throw an exception)
        $exception = NULL;
        try {
            $this->run2();
        } catch (Exception $exception) {

        }

        // in any case, exception or not, the task is ending so unlock the pid file
        flock($tasklock, LOCK_UN | LOCK_NB);
        ftruncate($tasklock, 0);
        fclose($tasklock);
        @unlink($lockfile);

        switch ($this->getState()) {
            case self::STATE_TODELETE:
                $this->delete();
                break;
            case self::STATE_TOSTOP:
                $this->setState(self::STATE_STOPPED);
                break;
        }

        // if something went wrong, report
        if ($exception)
            throw($exception);
    }

    abstract protected function run2();

    protected function loadSettings(SimpleXMLElement $sx_task_settings)
    {
        $this->period = (int) $sx_task_settings->period;
        if ($this->period <= 0 || $this->period >= 60 * 60)
            $this->period = 60;

        $this->maxrecs = (int) $sx_task_settings->maxrecs;
        if ($sx_task_settings->maxrecs < 10 || $sx_task_settings->maxrecs > 1000)
            $this->maxrecs = 100;
        $this->maxmegs = (int) $sx_task_settings->maxmegs;
        if ($sx_task_settings->maxmegs < 16 || $sx_task_settings->maxmegs > 512)
            $this->maxmegs = 24;
        $this->record_buffer_size = (int) $sx_task_settings->flush;
        if ($sx_task_settings->flush < 1 || $sx_task_settings->flush > 100)
            $this->record_buffer_size = 10;
    }

    protected function incrementLoops()
    {
        if ($this->getRunner() == self::RUNNER_SCHEDULER && ++ $this->loop >= $this->maxloops) {
            $this->log(sprintf(('%d loops done, restarting'), $this->loop));
            $this->setState(self::STATE_TORESTART);

            $this->running = false;
        }
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

    public function getID()
    {
        return $this->taskid;
    }

    function setProgress($done, $todo)
    {
        $p = ($todo > 0) ? ((100 * $done) / $todo) : -1;

        try {
            $conn = connection::getPDOConnection();
            $sql = 'UPDATE task2 SET completed = :p WHERE task_id = :taskid';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':p'      => $p, ':taskid' => $this->getID()));
            $stmt->closeCursor();
            $this->completed_percentage = $p;
        } catch (Exception $e) {

        }

        return $this;
    }
}
