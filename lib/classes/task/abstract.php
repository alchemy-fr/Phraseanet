<?php

use Monolog\Logger;

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

    // default min/max values for the 'restart task every n records' setting on tasks
    const MINRECS = 10;
    const MAXRECS = 100;
    // default min/max values for the 'overflow memory (Mo)' setting on tasks
    const MINMEGS = 20;
    const MAXMEGS = 256;
    // default min/max values for the 'period (seconds)' setting on tasks
    const MINPERIOD = 10;
    const MAXPERIOD = 3600;
    // default min/max values for the 'flush every n records' setting on tasks
    const MINFLUSH = 1;
    const MAXFLUSH = 100;

    const LOG_DEBUG       = Logger::DEBUG;
    const LOG_INFO        = Logger::INFO;
    const LOG_WARNING     = Logger::WARNING;
    const LOG_ERROR       = Logger::ERROR;
    const LOG_CRITICAL    = Logger::CRITICAL;
    const LOG_ALERT       = Logger::ALERT;

    /**
     *
     * @var Logger
     */
    protected $logger;
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
    protected $system = '';
    protected $dependencyContainer;

    public function __construct($taskid, Pimple $dependencyContainer, Logger $logger)
    {
        $this->dependencyContainer = $dependencyContainer;

        $this->logger = $logger;

        $this->taskid = (integer) $taskid;

        phrasea::use_i18n($this->dependencyContainer['locale']);

        $this->launched_by = array_key_exists("REQUEST_URI", $_SERVER) ? self::LAUCHED_BY_BROWSER : self::LAUCHED_BY_COMMANDLINE;

        try {
            $conn = connection::getPDOConnection($this->dependencyContainer);
        } catch (Exception $e) {
            $this->log($e->getMessage());
            $this->log(("Warning : abox connection lost, restarting in 10 min."));

            $this->sleep(60 * 10);

            $this->running = false;

            return '';
        }
        $sql = 'SELECT crashed, pid, status, active, settings, name, completed, runner
              FROM task2 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->getID()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (! $row) {
            throw new Exception('Unknown task id');
        }
        $this->title = $row['name'];
        $this->crash_counter = (integer) $row['crashed'];
        $this->active = ! ! $row['active'];
        $this->settings = $row['settings'];
        $this->runner = $row['runner'];
        $this->completed_percentage = (int) $row['completed'];
        $this->settings = $row['settings'];

        if (false !== $sx = @simplexml_load_string($this->settings)) {
            $this->loadSettings($sx);
        }
    }

    /**
     *
     * @return string
     */
    public function getClass()
    {
        return get_class($this);
    }

    /**
     * get the state of the task (task_abstract::STATE_*)
     *
     * @return String
     */
    public function getState()
    {
        static $stmt = NULL;
        $conn = connection::getPDOConnection($this->dependencyContainer);
        if (! $stmt) {
            $sql = 'SELECT status FROM task2 WHERE task_id = :taskid';
            $stmt = $conn->prepare($sql);
        }
        $stmt->execute(array(':taskid' => $this->taskid));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (! $row) {
            throw new Exception('Unknown task id');
        }
        unset($conn);

        return $row['status'];
    }

    /**
     * to be overwritten by tasks : ECHO text to be included in <head> in task interface
     */
    public function printInterfaceHEAD()
    {

    }

    /**
     * to be overwritten by tasks : ECHO javascript to be included in <head> in task interface
     */
    public function printInterfaceJS()
    {

    }

    /**
     *
     * @return boolean
     */
    public function hasInterfaceHTML()
    {
        return method_exists($this, "getInterfaceHTML");
    }

    /**
     * set the state of the task (task_abstract::STATE_*)
     *
     * @param  String                    $status
     * @throws Exception_InvalidArgument
     */
    public function setState($status)
    {
        $av_status = array(
            self::STATE_STARTED,
            self::STATE_TOSTOP,
            self::STATE_STOPPED,
            self::STATE_TORESTART,
            self::STATE_TOSTART,
            self::STATE_TODELETE
        );

        if ( ! in_array($status, $av_status)) {
            throw new Exception_InvalidArgument(sprintf('unknown status `%s`', $status));
        }

        $conn = connection::getPDOConnection($this->dependencyContainer);

        $sql = 'UPDATE task2 SET status = :status WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':status' => $status, ':taskid' => $this->getID()));
        $stmt->closeCursor();
        $this->log(sprintf("task %d <- %s", $this->getID(), $status));
    }

    /**
     *
     * @param  boolean        $active 'active' means 'auto-start when scheduler starts'
     * @return \task_abstract
     */
    public function setActive($active)
    {
        $conn = connection::getPDOConnection($this->dependencyContainer);

        $sql = 'UPDATE task2 SET active = :active WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':active' => ($active ? '1' : '0'), ':taskid' => $this->getID()));
        $stmt->closeCursor();

        $this->active = ! ! $active;

        return $this;
    }

    /**
     *
     * @param  string         $title
     * @return \task_abstract
     */
    public function setTitle($title)
    {
        $title = strip_tags($title);
        $conn = connection::getPDOConnection($this->dependencyContainer);

        $sql = 'UPDATE task2 SET name = :title WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':title'  => $title, ':taskid' => $this->getID()));
        $stmt->closeCursor();

        $this->title = $title;

        return $this;
    }

    /**
     *
     * @param  string                    $settings xml settings as STRING
     * @throws Exception_InvalidArgument if not proper xml
     * @return \task_abstract
     */
    public function setSettings($settings)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (!@$dom->loadXML($settings)) {
            throw new Exception_InvalidArgument('Bad XML');
        }

        $conn = connection::getPDOConnection($this->dependencyContainer);

        $sql = 'UPDATE task2 SET settings = :settings WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':settings' => $dom->saveXML(), ':taskid'   => $this->getID()));
        $stmt->closeCursor();

        $this->settings = $settings;

        $this->loadSettings(simplexml_load_string($dom->saveXML()));

        return $this;
    }

    /**
     *
     * @return \task_abstract
     */
    public function resetCrashCounter()
    {
        $conn = connection::getPDOConnection($this->dependencyContainer);

        $sql = 'UPDATE task2 SET crashed = 0 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->getID()));
        $stmt->closeCursor();

        $this->crash_counter = 0;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function getCrashCounter()
    {
        return $this->crash_counter;
    }

    /**
     *
     * @return int
     */
    public function incrementCrashCounter()
    {
        $conn = connection::getPDOConnection($this->dependencyContainer);

        $sql = 'UPDATE task2 SET crashed = crashed + 1 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->getID()));
        $stmt->closeCursor();

        return ++ $this->crash_counter;
    }

    /**
     *
     * @return string
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * 'active' means 'auto-start when scheduler starts'
     *
     * @return boolean
     *
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     *
     * @return int
     */
    public function getCompletedPercentage()
    {
        return $this->completed_percentage;
    }

    abstract public function getName();

    abstract public function help();

    /**
     *
     * @return enum (self::RUNNER_MANUAL or self::RUNNER_SCHEDULER)
     */
    public function getRunner()
    {
        return $this->runner;
    }

    /**
     *
     * @param  enum                      $runner (self::RUNNER_MANUAL or self::RUNNER_SCHEDULER)
     * @throws Exception_InvalidArgument
     * @return \task_abstract
     */
    public function setRunner($runner)
    {
        if ($runner != self::RUNNER_MANUAL && $runner != self::RUNNER_SCHEDULER) {
            throw new Exception_InvalidArgument(sprintf('unknown runner `%s`', $runner));
        }

        $this->runner = $runner;

        $conn = connection::getPDOConnection($this->dependencyContainer);
        $sql = 'UPDATE task2 SET runner = :runner WHERE task_id = :taskid';

        $params = array(
            ':taskid' => $this->getID()
            , ':runner' => $this->runner
        );

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     *
     */
    public function delete()
    {
        if ( ! $this->getPID()) { // do not delete a running task
            $conn = connection::getPDOConnection($this->dependencyContainer);
            $sql = "DELETE FROM task2 WHERE task_id = :task_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':task_id' => $this->getID()));
            $stmt->closeCursor();

            $lock_file = __DIR__ . '/../../../tmp/locks/task_' . $this->getID() . '.lock';
            @unlink($lock_file);
        }
    }

    /**
     * set last execution time to now()
     */
    public function setLastExecTime()
    {
        $conn = connection::getPDOConnection($this->dependencyContainer);
        $sql = 'UPDATE task2 SET last_exec_time=NOW() WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->getID()));
        $stmt->closeCursor();
    }

    /**
     * Return the last time the task was executed
     *
     * @return null|\DateTime
     */
    public function getLastExecTime()
    {
        $conn = connection::getPDOConnection($this->dependencyContainer);

        $sql = 'SELECT last_exec_time FROM task2 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->getID()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $time = null;
        if ($row['last_exec_time'] != '0000-00-00 00:00:00') {
            $time = new \DateTime($row['last_exec_time']);
        }

        return $time;
    }

    /**
     *
     * @return null|integer
     * pid (int) of the task
     * NULL : the pid file is not locked (task no running)
     */
    public function getPID()
    {
        $pid = NULL;

        $lockfile = $this->getLockfilePath();

        if (($fd = fopen($lockfile, 'a+')) != FALSE) {
            if (flock($fd, LOCK_EX | LOCK_NB) === FALSE) {
                // already locked ? : task running
                $pid = (integer) fgets($fd);
            } else {
                // can lock : not running
                flock($fd, LOCK_UN);
            }
            fclose($fd);
        }

        return $pid;
    }

    /**
     * set to false to ask the task to quit its loop
     * @param boolean $stat
     *
     */
    public function setRunning($stat)
    {
        $this->running = $stat;
    }

    protected function pause($when_started = 0)
    {
        $this->log($this->records_done . ' records done');
        if ($this->running) {       // && $this->records_done == 0)
            $when_started = time() - $when_started;
            if ($when_started < $this->period) {
                for ($t = $this->period - $when_started; $this->running && $t > 0; $t --) { // DON'T do sleep($this->period - $when_started) because it prevents ticks !
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

    /**
     * sleep n seconds
     *
     * @param  int                       $nsec
     * @throws \InvalidArgumentException
     */
    protected function sleep($nsec)
    {
        $nsec = (integer) $nsec;
        if ($nsec < 0) {
            throw new \InvalidArgumentException(sprintf("(%s) is not > 0"));
        }

        while ($this->running && $nsec -- > 0) {
            sleep(1);
        }
    }

    /**
     *
     * @return string fullpath to the pid file for the task
     */
    private function getLockfilePath()
    {
        $lockdir = $this->dependencyContainer['phraseanet.registry']->get('GV_RootPath') . 'tmp/locks/';
        $lockfilePath = ($lockdir . 'task_' . $this->getID() . '.lock');

        return $lockfilePath;
    }

    /**
     *
     * @return resource  file descriptor of the OPENED pid file
     * @throws Exception if file is already locked (task running)
     */
    private function lockTask()
    {
        $lockfile = $this->getLockfilePath();

        $lockFD = fopen($lockfile, 'a+');

        $locker = true;
        if (flock($lockFD, LOCK_EX | LOCK_NB, $locker) === FALSE) {
            $this->log("runtask::ERROR : task already running.");
            fclose($lockFD);

            throw new Exception('task already running.', self::ERR_ALREADY_RUNNING);
        }

        // here we run the task
        ftruncate($lockFD, 0);
        fwrite($lockFD, '' . getmypid());
        fflush($lockFD);

        // for windows : unlock then lock shared to allow OTHER processes to read the file
        // too bad : no critical section nor atomicity
        flock($lockFD, LOCK_UN);
        flock($lockFD, LOCK_SH);

        return $lockFD;
    }

    final public function run($runner)
    {
        $lockFD = $this->lockTask();

        $this->setRunner($runner);
        $this->setState(self::STATE_STARTED);

        // run the real code of the task -into the task's class- (may throw an exception)
        $exception = NULL;
        try {
            $this->run2();
        } catch (\Exception $exception) {

        }

        if ($this->getState() === self::STATE_STARTED && $this->runner === self::RUNNER_MANUAL) {
            $this->setState(self::STATE_STOPPED);
        }

        // in any case, exception or not, the task is ending so unlock the pid file
        $this->unlockTask($lockFD);

        // if something went wrong, report
        if ($exception) {
            throw $exception;
        }
    }

    /**
     *
     * @param resource $lockFD file descriptor of the OPENED lock file
     */
    private function unlockTask($lockFD)
    {
        flock($lockFD, LOCK_UN | LOCK_NB);
        ftruncate($lockFD, 0);
        fclose($lockFD);

        $lockfile = $this->getLockfilePath();
        @unlink($lockfile);

        switch ($this->getState()) {
            case self::STATE_TODELETE:
                $this->delete();
                break;
            case self::STATE_TOSTOP:
                $this->setState(self::STATE_STOPPED);
                break;
        }
    }

    abstract protected function run2();

    protected function processLoop(&$box, &$rs)
    {
        $ret = self::STATE_OK;

        $rowstodo = count($rs);
        $rowsdone = 0;

        if ($rowstodo > 0) {
            $this->setProgress(0, $rowstodo);
        }

        foreach ($rs as $row) {

            try {
                // process one record
                $this->processOneContent($box, $row);
            } catch (Exception $e) {
                $this->log("Exception : " . $e->getMessage() . " " . basename($e->getFile()) . " " . $e->getLine());
            }

            $this->records_done ++;
            $this->setProgress($rowsdone, $rowstodo);

            // post-process
            $this->postProcessOneContent($box, $row);

            $rowsdone ++;

            $current_memory = memory_get_usage();
            if ($current_memory >> 20 >= $this->maxmegs) {
                $this->log(sprintf("Max memory (%s M) reached (actual is %.02f M)", $this->maxmegs, ($current_memory >> 10) / 1024), self::LOG_ERROR);
                $this->running = FALSE;
                $ret = self::STATE_MAXMEGSREACHED;
            }

            if ($this->records_done >= (integer) ($this->maxrecs)) {
                $this->log(sprintf("Max records done (%s) reached (actual is %s)", $this->maxrecs, $this->records_done));
                $this->running = FALSE;
                $ret = self::STATE_MAXRECSDONE;
            }

            try {
                if ($this->getState() == self::STATE_TOSTOP) {
                    $this->running = FALSE;
                    $ret = self::STATE_TOSTOP;
                }
            } catch (Exception $e) {
                $this->running = FALSE;
            }

            if (! $this->running) {
                break;
            }
        }
        //
        // if nothing was done, at least check the status
        if ($rowsdone == 0 && $this->running) {

            $current_memory = memory_get_usage();
            if ($current_memory >> 20 >= $this->maxmegs) {
                $this->log(sprintf("Max memory (%s M) reached (current is %.02f M)", $this->maxmegs, ($current_memory >> 10) / 1024), self::LOG_ERROR);
                $this->running = FALSE;
                $ret = self::STATE_MAXMEGSREACHED;
            }

            if ($this->records_done >= (integer) ($this->maxrecs)) {
                $this->log(sprintf("Max records done (%s) reached (actual is %s)", $this->maxrecs, $this->records_done));
                $this->running = FALSE;
                $ret = self::STATE_MAXRECSDONE;
            }

            try {
                $status = $this->getState();
                if ($status == self::STATE_TOSTOP) {
                    $this->running = FALSE;
                    $ret = self::STATE_TOSTOP;
                }
            } catch (Exception $e) {
                $this->running = FALSE;
            }
        }

        if ($rowstodo > 0) {
            $this->setProgress(0, 0);
        }

        return $ret;
    }

    protected function loadSettings(SimpleXMLElement $sx_task_settings)
    {
        $this->period = (integer) $sx_task_settings->period;
        if ($this->period < self::MINPERIOD || $this->period > self::MAXPERIOD) {
            $this->period = self::MINPERIOD;
        }

        $this->maxrecs = (integer) $sx_task_settings->maxrecs;
        if ($sx_task_settings->maxrecs < self::MINRECS || $sx_task_settings->maxrecs > self::MAXRECS) {
            $this->maxrecs = self::MINRECS;
        }

        $this->maxmegs = (integer) $sx_task_settings->maxmegs;
        if ($sx_task_settings->maxmegs < self::MINMEGS || $sx_task_settings->maxmegs > self::MAXMEGS) {
            $this->maxmegs = self::MINMEGS;
        }

        $this->record_buffer_size = (integer) $sx_task_settings->flush;
        if ($sx_task_settings->flush < self::MINFLUSH || $sx_task_settings->flush > self::MAXFLUSH) {
            $this->record_buffer_size = self::MINFLUSH;
        }
    }

    protected function incrementLoops()
    {
        if ($this->getRunner() == self::RUNNER_SCHEDULER && ++ $this->loop >= $this->maxloops) {
            $this->log(sprintf(('%d loops done, restarting'), $this->loop));
            $this->setState(self::STATE_TORESTART);

            $this->running = false;
        }
    }

    public function traceRam($msg = '')
    {
        static $lastt = null;
        $t = explode(' ', ($ut = microtime()));
        if ($lastt === null) {
            $lastt = $t;
        }
        $dt = ($t[0] - $lastt[0]) + ($t[1] - $lastt[1]);

        $m = memory_get_usage() >> 10;
        $d = debug_backtrace(false);

        $lastt = $t;
        $this->logger->addDebug(memory_get_usage() . " -- " . memory_get_usage(true));
    }

    public function log($message, $level=self::LOG_INFO)
    {
        // nb : self::log_levels ARE standard log levels, ok with monolog
        $this->logger->addRecord($level, $message);

        return $this;
    }

    public static function interfaceAvailable()
    {
        return true;
    }

    public function getInterfaceHTML()
    {
        return '';
    }

    public function graphic2xml($oldxml)
    {
        return $oldxml;
    }

    /**
     *
     * @param  appbox        $appbox
     * @param  string        $class_name
     * @param  string        $settings   (xml string)
     * @return task_abstract
     */
    public static function create(\Pimple $dependencyContainer, $class_name, $settings = null)
    {
        if ( ! class_exists($class_name)) {
            throw new Exception('Unknown task class');
        }

        $sql = 'INSERT INTO task2
                    (task_id, usr_id_owner, status, crashed, active,
                      name, last_exec_time, class, settings)
                    VALUES
                    (null, 0, "stopped", 0, :active,
                      :name, "0000/00/00 00:00:00", :class, :settings)';

        $domdoc = new DOMDocument();
        if ($settings && ! $domdoc->loadXML($settings)) {
            throw new Exception('settings invalide');
        } elseif (! $settings) {
            $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n</tasksettings>";
        }

        $params = array(
            ':active'   => 1
            , ':name'     => ''
            , ':class'    => $class_name
            , ':settings' => $settings
        );
        $stmt = $dependencyContainer['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $tid = $dependencyContainer['phraseanet.appbox']->get_connection()->lastInsertId();

        $task = new $class_name($tid, $dependencyContainer, $dependencyContainer['monolog']);
        $task->setTitle($task->getName());

        return $task;
    }

    public function getUsage()
    {
        global $argc, $argv;
        $t = "usage: " . $argv[0] . " [options]\noptions:\n";
        foreach ($this->argt as $n => $v) {
            $t .= "\t" . $n . $v["usage"] . "\n";
        }

        return $t;
    }

    /**
     *
     * @return int id of the task
     */
    public function getID()
    {
        return $this->taskid;
    }

    /**
     *
     * @param  int            $done
     * @param  int            $todo
     * @return \task_abstract
     */
    public function setProgress($done, $todo)
    {
        $p = ($todo > 0) ? ((100 * $done) / $todo) : -1;

        try {
            $conn = connection::getPDOConnection($this->dependencyContainer);
            $sql = 'UPDATE task2 SET completed = :p WHERE task_id = :taskid';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(
                ':p'      => $p,
                ':taskid' => $this->getID()
            ));
            $stmt->closeCursor();
            $this->completed_percentage = $p;
        } catch (Exception $e) {

        }

        return $this;
    }
}
