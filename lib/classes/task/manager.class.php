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
class task_manager
{
    const STATE_STOPPED = 'stopped';
    const STATE_STOPPING = 'stopping';
    const STATE_STARTED = 'started';
    const STATE_TOSTOP = 'tostop';

    protected $appbox;
    protected $tasks;

    public function __construct(appbox &$appbox)
    {
        $this->appbox = $appbox;

        return $this;
    }

    public function getTasks($refresh = false)
    {
        if ($this->tasks && ! $refresh) {
            return $this->tasks;
        }

        $sql = "SELECT task2.* FROM task2 ORDER BY task_id ASC";
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $tasks = array();

        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $lockdir = $appbox->get_registry()->get('GV_RootPath') . 'tmp/locks/';

        foreach ($rs as $row) {
            $row['pid'] = NULL;

            $classname = $row['class'];
            if ( ! class_exists($classname)) {
                continue;
            }
            try {
//        if( ($lock = fopen( $lockdir . 'task.'.$row['task_id'].'.lock', 'a+')) )
//        {
//          if (flock($lock, LOCK_SH | LOCK_NB) === FALSE)
//          {
//            // already locked : running !
//            $row['pid'] = fgets($lock, 512);
//          }
//          else
//          {
//            // can lock : not running
//            flock($lock, LOCK_UN);
//          }
//          fclose($lock);
//        }
                $tasks[$row['task_id']] = new $classname($row['task_id']);
            } catch (Exception $e) {

            }
        }

        $this->tasks = $tasks;

        return $this->tasks;
    }

    /**
     *
     * @param int $task_id
     * @return task_abstract
     */
    public function getTask($task_id)
    {
        $tasks = $this->getTasks();

        if ( ! isset($tasks[$task_id])) {
            throw new Exception_NotFound('Unknown task_id ' . $task_id);
        }

        return $tasks[$task_id];
    }

    public function setSchedulerState($status)
    {
        $av_status = array(
            self::STATE_STARTED,
            self::STATE_STOPPED,
            self::STATE_STOPPING,
            self::STATE_TOSTOP
        );

        if ( ! in_array($status, $av_status))
            throw new Exception(sprintf('unknown status `%s` ', $status));

        $sql = "UPDATE sitepreff SET schedstatus = :schedstatus, schedqtime=NOW()";
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':schedstatus' => $status));
        $stmt->closeCursor();

        return $this;
    }

    public function getSchedulerState()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $sql = "SELECT UNIX_TIMESTAMP()-UNIX_TIMESTAMP(schedqtime) AS qdelay
            , schedstatus AS status FROM sitepreff";
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $ret = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $pid = NULL;

        $lockdir = $appbox->get_registry()->get('GV_RootPath') . 'tmp/locks/';
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

        if ($pid === NULL && $ret['status'] !== 'stopped') {
            // auto fix
            $this->appbox->get_connection()->exec('UPDATE sitepreff SET schedstatus=\'stopped\'');
            $ret['status'] = 'stopped';
        }
        $ret['pid'] = $pid;

        return $ret;
    }

    public static function getAvailableTasks()
    {
        $registry = registry::get_instance();
        $taskdir = array($registry->get('GV_RootPath') . "lib/classes/task/period/"
            , $registry->get('GV_RootPath') . "config/classes/task/period/"
        );

        $tasks = array();
        foreach ($taskdir as $path) {
            if (($hdir = @opendir($path)) != FALSE) {
                $max = 9999;
                while (($max -- > 0) && (($file = readdir($hdir)) !== false)) {
                    if ( ! is_file($path . '/' . $file) || substr($file, 0, 1) == "." || substr($file, -10) != ".class.php") {
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
