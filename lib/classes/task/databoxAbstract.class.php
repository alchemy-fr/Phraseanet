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
abstract class task_databoxAbstract extends task_abstract
{

//  abstract public function help();
//
//  abstract public function getName();

  protected $mono_sbas_id;

  abstract protected function retrieve_sbas_content(databox $databox);

  abstract protected function process_one_content(databox $databox, Array $row);

  abstract protected function flush_records_sbas();

  abstract protected function post_process_one_content(databox $databox, Array $row);

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
        $sql = 'SELECT sbas_id, task2.* FROM sbas, task2 WHERE task_id = :taskid';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':taskid' => $this->get_task_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
      foreach ($rs as $row)
      {
        if (!$this->running)
          break;

        $this->sbas_id = (int) $row['sbas_id'];

        if ($this->mono_sbas_id && $this->sbas_id !== $this->mono_sbas_id)
        {
          continue;
        }
        if ($this->mono_sbas_id)
        {
          $this->log('This task works on ' . phrasea::sbas_names($this->mono_sbas_id));
        }

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
        $this->process_sbas()
                ->check_current_state()
                ->flush_records_sbas();
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
  protected function process_sbas()
  {
    $tsub = array();
    $connbas = false;

    try
    {
      $databox = databox::get_instance($this->sbas_id);
      $connbas = $databox->get_connection();
      /**
       * GET THE RECORDS TO PROCESS ON CURRENT SBAS
       */
      $rs = $this->retrieve_sbas_content($databox);
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
        $this->process_one_content($databox, $row);
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
      $this->post_process_one_content($databox, $row);

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

