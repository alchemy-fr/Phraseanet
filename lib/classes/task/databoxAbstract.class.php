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
        $task_must_delete = FALSE; // if the task must be deleted (suicide) after run
        $this->running = TRUE;
        while ($this->running) {
            try {
                $conn = connection::getPDOConnection();
            } catch (Exception $e) {
                $this->log($e->getMessage());
                if ($this->get_runner() == self::RUNNER_SCHEDULER) {
                    $this->log(("Warning : abox connection lost, restarting in 10 min."));

                    for ($t = 60 * 10; $this->running && $t; $t -- ) // DON'T do sleep(600) because it prevents ticks !
                        sleep(1);
                    // because connection is lost we cannot change status to 'torestart'
                    // anyway the current status 'running' with no pid
                    // will enforce the scheduler to restart the task
                } else {
                    // runner = manual : can't restart so simply quit
                }
                $this->running = FALSE;
                return;
            }

            $this->set_last_exec_time();
            try {
                if ($this->mono_sbas_id) {
                    $sql = 'SELECT sbas_id, task2.* FROM sbas, task2 WHERE task_id=:taskid AND sbas_id=:sbas_id';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute(array(':taskid' => $this->get_task_id(), ':sbas_id' => $this->mono_sbas_id));
                } else {
                    $sql = 'SELECT sbas_id, task2.* FROM sbas, task2 WHERE task_id = :taskid';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute(array(':taskid' => $this->get_task_id()));
                }
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                $this->records_done = 0;
                $duration = time();
            } catch (Exception $e) {
                // failed sql, simply return
                $this->running = FALSE;
                return;
            }

            foreach ($rs as $row) { // every sbas
                if ( ! $this->running)
                    break;

                $this->sbas_id = (int) $row['sbas_id'];
                $this->log('This task works now on ' . phrasea::sbas_names($this->sbas_id));

                try {
                    $this->load_settings(simplexml_load_string($row['settings']));
                } catch (Exception $e) {
                    $this->log($e->getMessage());
                    continue;
                }

                $process_ret = $this->process_sbas();

                // printf("%s (%d) process_ret=%s \n", __FILE__, __LINE__, var_export($process_ret, true));
                // $this->check_current_xxxstate();
                switch ($process_ret) {
                    case self::STATE_MAXMEGSREACHED:
                    case self::STATE_MAXRECSDONE:
                        if ($this->get_runner() == self::RUNNER_SCHEDULER) {
                            $this->set_status(self::STATUS_TORESTART);
                            $this->running = FALSE;
                        }
                        break;

                    case self::STATUS_TOSTOP:
                        $this->set_status(self::STATUS_TOSTOP);
                        $this->running = FALSE;
                        break;

                    case self::STATUS_TODELETE: // formal 'suicidable'
                        // DO NOT SUICIDE IN THE LOOP, may have to work on other sbas !!!
                        // $this->set_status(self::STATUS_TODELETE);
                        // $this->log('task will self delete');
                        // $this->running = FALSE;
                        $task_must_delete = TRUE;
                        break;

                    case self::STATE_OK:
                        break;
                }

                $this->flush_records_sbas();
            }  // foreach sbas

            $this->increment_loops();
            $this->pause($duration);
        } // while($this->running)

        if ($task_must_delete) {
            $this->set_status(self::STATUS_TODELETE);
            $this->log('task will self delete');
        }
        return;
    }

    /**
     *
     * @return <type>
     */
    protected function process_sbas()
    {
        $ret = self::STATE_OK;

        $tsub = array();
        $connbas = false;

        try {
            // get the records to process
            $databox = databox::get_instance($this->sbas_id);
            $connbas = $databox->get_connection();
            $rs = $this->retrieve_sbas_content($databox);
        } catch (Exception $e) {
            $this->log('Error  : ' . $e->getMessage());
            $rs = array();
        }

        $rowstodo = count($rs);
        $rowsdone = 0;

        if ($rowstodo > 0)
            $this->setProgress(0, $rowstodo);

        foreach ($rs as $row) {
            try {
                // process one record
                $this->process_one_content($databox, $row);
            } catch (Exception $e) {
                $this->log("Exception : " . $e->getMessage() . " " . basename($e->getFile()) . " " . $e->getLine());
            }

            $this->records_done ++;
            $this->setProgress($rowsdone, $rowstodo);

            // post-process
            $this->post_process_one_content($databox, $row);

            // $this->check_memory_usage();
            $current_memory = memory_get_usage();
            if ($current_memory >> 20 >= $this->maxmegs) {
                $this->log(sprintf("Max memory (%s M) reached (actual is %s M)", $this->maxmegs, $current_memory));
                $this->running = FALSE;
                $ret = self::STATE_MAXMEGSREACHED;
            }

            // $this->check_records_done();
            if ($this->records_done >= (int) ($this->maxrecs)) {
                $this->log(sprintf("Max records done (%s) reached (actual is %s)", $this->maxrecs, $this->records_done));
                $this->running = FALSE;
                $ret = self::STATE_MAXRECSDONE;
            }

            // $this->check_task_status();
            try {
                $status = $this->get_status();
                // printf("%s (%d) status=%s \n", __FILE__, __LINE__, var_export($status, true));
                if ($status == self::STATUS_TOSTOP) {
                    $this->running = FALSE;
                    $ret = self::STATUS_TOSTOP;
                }
            } catch (Exception $e) {
                $this->running = FALSE;
//				$this->task_status = self::STATUS_TOSTOP;
//				$this->return_xxxvalue = self::RETURNSTATUS_STOPPED;
            }
//			if($this->task_status == self::STATUS_TOSTOP)
//				$this->running = false;


            if ( ! $this->running)
                break;
        } // foreach($rs as $row)
        // if nothing was done, at least check the status
        if (count($rs) == 0 && $this->running) {
            // $this->check_memory_usage();
            $current_memory = memory_get_usage();
            if ($current_memory >> 20 >= $this->maxmegs) {
                $this->log(sprintf("Max memory (%s M) reached (current is %s M)", $this->maxmegs, $current_memory));
                $this->running = FALSE;
                $ret = self::STATE_MAXMEGSREACHED;
            }

            // $this->check_records_done();
            if ($this->records_done >= (int) ($this->maxrecs)) {
                $this->log(sprintf("Max records done (%s) reached (actual is %s)", $this->maxrecs, $this->records_done));
                $this->running = FALSE;
                $ret = self::STATE_MAXRECSDONE;
            }

            // $this->check_task_status();
            try {
                $status = $this->get_status();
                // printf("%s (%d) status=%s \n", __FILE__, __LINE__, var_export($status, true));
                if ($status == self::STATUS_TOSTOP) {
                    $this->running = FALSE;
                    $ret = self::STATUS_TOSTOP;
                    //				$this->task_status = self::STATUS_TOSTOP;
                    //				$this->return_xxxvalue = self::RETURNSTATUS_STOPPED;
                }
            } catch (Exception $e) {
                $this->running = FALSE;
                //			$this->task_status = self::STATUS_TOSTOP;
                //			$this->return_xxxvalue = self::RETURNSTATUS_STOPPED;
            }
        }

        // close the cnx to the dbox
        if ($connbas instanceof PDO) {
            $connbas->close();
            unset($connbas);
        }

        if ($rowstodo > 0)
            $this->setProgress(0, 0);

        return($ret);
    }
}

