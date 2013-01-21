<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Monolog\Logger;
use Alchemy\Phrasea\Application;
use Symfony\Component\Process\Process;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_manager
{
    const STATE_STOPPED = 'stopped';
    const STATE_STOPPING = 'stopping';
    const STATE_STARTED = 'started';
    const STATE_TOSTOP = 'tostop';

    protected $app;
    protected $logger;
    protected $tasks;

    public function __construct(Application $app, Logger $logger)
    {
        $this->app = $app;
        $this->logger = $logger;

        return $this;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * status of scheduler and tasks
     * used do refresh the taskmanager page
     *
     * @return array
     */
    public function toArray()
    {
        $ret = array(
            'time'      => date("H:i:s"),
            'scheduler' => $this->getSchedulerState(),
            'tasks'     => array()
        );

        foreach ($this->getTasks(true) as $task) {
            if ($task->getState() == self::STATE_TOSTOP && $task->getPID() === NULL) {
                // fix
                $task->setState(self::STATE_STOPPED);
            }
            $id = $task->getID();
            $ret['tasks'][$id] = array(
                'id'        => $id,
                'pid'       => $task->getPID(),
                'crashed'   => $task->getCrashCounter(),
                'completed' => $task->getCompletedPercentage(),
                'status'    => $task->getState()
            );
        }

        return $ret;
    }

    public function getTasks($refresh = false)
    {
        if ($this->tasks && !$refresh) {
            return $this->tasks;
        }

        $sql = "SELECT task2.* FROM task2 ORDER BY task_id ASC";
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $tasks = array();

        foreach ($rs as $row) {
            $row['pid'] = NULL;

            $classname = $row['class'];
            if (!class_exists($classname)) {
                continue;
            }
            try {
                $tasks[$row['task_id']] = new $classname($row['task_id'], $this->app, $this->logger);
            } catch (Exception $e) {

            }
        }

        $this->tasks = $tasks;

        return $this->tasks;
    }

    /**
     *
     * @param  int           $task_id
     * @return task_abstract
     */
    public function getTask($task_id)
    {
        $tasks = $this->getTasks(false);

        if (!isset($tasks[$task_id])) {
            throw new Exception_NotFound('Unknown task_id ' . $task_id);
        }

        return $tasks[$task_id];
    }

    public function getSchedulerProcess()
    {
        $phpcli = $this->app['phraseanet.registry']->get('php_binary');

        $cmd = $phpcli . ' -f ' . $this->app['phraseanet.registry']->get('GV_RootPath') . "bin/console scheduler:start";

        return new Process($cmd);
    }

    public function setSchedulerState($status)
    {
        $av_status = array(
            self::STATE_STARTED,
            self::STATE_STOPPED,
            self::STATE_STOPPING,
            self::STATE_TOSTOP
        );

        if (!in_array($status, $av_status))
            throw new Exception(sprintf('unknown status `%s` ', $status));

        $sql = "UPDATE sitepreff SET schedstatus = :schedstatus, schedqtime=NOW()";
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':schedstatus' => $status));
        $stmt->closeCursor();

        return $this;
    }

    public function getSchedulerState()
    {
        $sql = "SELECT UNIX_TIMESTAMP()-UNIX_TIMESTAMP(schedqtime) AS qdelay
            , schedqtime AS updated_on
            , schedstatus AS status FROM sitepreff";
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $ret = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $pid = NULL;

        $lockdir = $this->app['phraseanet.registry']->get('GV_RootPath') . 'tmp/locks/';
        if (($schedlock = fopen($lockdir . 'scheduler.lock', 'a+')) != FALSE) {
            if (flock($schedlock, LOCK_EX | LOCK_NB) === FALSE) {
                // already locked : running !
                $pid = trim(fgets($schedlock, 512));
            } else {
                // can lock : not running
                flock($schedlock, LOCK_UN);
            }
            fclose($schedlock);
        }

        if ($ret['updated_on'] == '0000-00-00 00:00:00') {
            $ret['updated_on'] = null;
        } else {
            $ret['updated_on'] = new \DateTime($ret['updated_on']);
        }

        if ($pid === NULL && $ret['status'] !== 'stopped') {
            // auto fix
            $this->app['phraseanet.appbox']->get_connection()->exec('UPDATE sitepreff SET schedstatus=\'stopped\'');
            $ret['status'] = 'stopped';
        }
        $ret['pid'] = $pid;

        return $ret;
    }

    /**
     * Returns true if Pcntl posix supported is enabled, false otherwise
     *
     * @return Boolean
     */
    public static function isPosixPcntlSupported()
    {
        return extension_loaded('pcntl') && extension_loaded('posix');
    }

    public static function getAvailableTasks()
    {
        $taskdir = array(
            __DIR__ . "/period/",
            __DIR__ . "/../../../config/classes/task/period/",
        );

        $tasks = array();
        foreach ($taskdir as $path) {
            if (($hdir = @opendir($path)) != FALSE) {
                $max = 9999;
                while (($max-- > 0) && (($file = readdir($hdir)) !== false)) {
                    if (!is_file($path . '/' . $file) || substr($file, 0, 1) == "." || substr($file, -10) != ".php") {
                        continue;
                    }

                    $classname = 'task_period_' . substr($file, 0, strlen($file) - 10);

                    try {
                        //      $testclass = new $classname(null);
                        if ($classname::interfaceAvailable()) {
                            $tasks[] = array("class" => $classname, "name"  => $classname::getName(), "err"   => null);
                        }
                    } catch (Exception $e) {

                    }
                }
                closedir($hdir);
            }
        }

        return $tasks;
    }
}
