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
abstract class task_appboxAbstract extends task_abstract
{

  abstract protected function retrieve_content(appbox $appbox);

  abstract protected function process_one_content(appbox $appbox, Array $row);

  abstract protected function post_process_one_content(appbox $appbox, Array $row);

  protected function run2()
  {
    while ($this->running)
    {
      try
      {
        $conn = connection::getPDOConnection();
      }
      catch (Exception $e)
      {
        $this->log($e->getMessage());
        $this->log(("Warning : abox connection lost, restarting in 10 min."));
        sleep(60 * 10);
        $this->running = false;
        $this->return_value = self::RETURNSTATUS_TORESTART;

        return;
      }

      $this->set_last_exec_time();

      try
      {
        $sql = 'SELECT task2.* FROM task2 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->get_task_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->records_done = 0;
        $duration = time();
      }
      catch (Exception $e)
      {
        $this->task_status = self::STATUS_TOSTOP;
        $this->return_value = self::RETURNSTATUS_STOPPED;
        $rs = array();
      }
      if ($row)
      {
        if (!$this->running)
          break;

        $appbox = appbox::get_instance(\bootstrap::getCore());
        try
        {
          $this->load_settings(simplexml_load_string($row['settings']));
        }
        catch (Exception $e)
        {
          $this->log($e->getMessage());
          continue;
        }

        $this->current_state = self::STATE_OK;
        $this->process($appbox)
                ->check_current_state();
      }

      $this->increment_loops();
      $this->pause($duration);
    }

    return;
  }

  /**
   *
   * @return <type>
   */
  protected function process(appbox $appbox)
  {
    $conn = $appbox->get_connection();
    $tsub = array();
    try
    {
      /**
       * GET THE RECORDS TO PROCESS ON CURRENT SBAS
       */
      $rs = $this->retrieve_content($appbox);
    }
    catch (Exception $e)
    {
      $this->log('Error  : ' . $e->getMessage());
      $rs = array();
    }

    $rowstodo = count($rs);
    $rowsdone = 0;

    if ($rowstodo > 0)
      $this->setProgress(0, $rowstodo);

    foreach ($rs as $row)
    {
      try
      {

        /**
         * PROCESS ONE RECORD
         */
        $this->process_one_content($appbox, $row);
      }
      catch (Exception $e)
      {
        $this->log("Exception : " . $e->getMessage()
                . " " . basename($e->getFile()) . " " . $e->getLine());
      }

      $this->records_done++;
      $this->setProgress($rowsdone, $rowstodo);

      /**
       * POST COIT
       */
      $this->post_process_one_content($appbox, $row);

      $this->check_memory_usage()
              ->check_records_done()
              ->check_task_status();

      if (!$this->running)
        break;
    }


    $this->check_memory_usage()
            ->check_records_done()
            ->check_task_status();

    if ($rowstodo > 0)
      $this->setProgress(0, 0);

    return $this;
  }

}

