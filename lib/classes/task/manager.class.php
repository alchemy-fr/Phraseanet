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
class task_manager
{
  const STATUS_SCHED_STOPPED = 'stopped';
  const STATUS_SCHED_STOPPING = 'stopping';
  const STATUS_SCHED_STARTED = 'started';
  const STATUS_SCHED_TOSTOP = 'tostop';

  protected $appbox;
  protected $tasks;

  public function __construct(appbox &$appbox)
  {
    $this->appbox = $appbox;

    return $this;
  }

  public function get_tasks($refresh = false)
  {
    if ($this->tasks && !$refresh)

      return $this->tasks;
    $sql = "SELECT task2.* FROM task2 ORDER BY task_id ASC";
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $tasks = array();

    foreach ($rs as $row)
    {
      $classname = $row['class'];
      if (!class_exists($classname))
        continue;
      try
      {
        $tasks[$row['task_id']] = new $classname($row['task_id']);
      }
      catch (Exception $e)
      {

      }
    }

    $this->tasks = $tasks;

    return $this->tasks;
  }

  /**
   *
   * @param <type> $task_id
   * @return task_abstract
   */
  public function get_task($task_id)
  {
    $tasks = $this->get_tasks();

    if (!isset($tasks[$task_id]))
      throw new Exception('Unknown task_id');

    return $tasks[$task_id];
  }

  public function set_sched_status($status)
  {
    $av_status = array(
        self::STATUS_SCHED_STARTED
        , self::STATUS_SCHED_STOPPED
        , self::STATUS_SCHED_STOPPING
        , self::STATUS_SCHED_TOSTOP
    );

    if (!in_array($status, $av_status))
      throw new Exception(sprintf('unknown status `%s` ', $status));

    $sql = "UPDATE sitepreff SET schedstatus = :schedstatus, schedqtime=NOW()";
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':schedstatus' => $status));
    $stmt->closeCursor();

    return $this;
  }

  public function get_scheduler_state()
  {
    $sql = "SELECT schedstatus,
            UNIX_TIMESTAMP()-UNIX_TIMESTAMP(schedqtime) AS schedqdelay, schedpid
          FROM sitepreff";
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    return $row;
  }

}
