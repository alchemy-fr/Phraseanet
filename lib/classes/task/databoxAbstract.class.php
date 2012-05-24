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
    protected $mono_sbas_id;

    abstract protected function retrieveSbasContent(databox $databox);

    abstract protected function processOneContent(databox $databox, Array $row);

    abstract protected function flushRecordsSbas();

    abstract protected function postProcessOneContent(databox $databox, Array $row);

    protected function run2()
    {
        $task_must_delete = FALSE; // if the task must be deleted (suicide) after run
        $this->running = TRUE;
        while ($this->running) {
            try {
                $conn = connection::getPDOConnection();
            } catch (PDOException $e) {
                $this->log($e->getMessage());
                if ($this->getRunner() == self::RUNNER_SCHEDULER) {
                    $this->log(("Warning : abox connection lost, restarting in 10 min."));

                    // DON'T do sleep(600) because it prevents ticks !
                    for ($t = 60 * 10; $this->running && $t; $t -- ) {
                        sleep(1);
                    }
                    // because connection is lost we cannot change status to 'torestart'
                    // anyway the current status 'running' with no pid
                    // will enforce the scheduler to restart the task
                } else {
                    // runner = manual : can't restart so simply quit
                }
                $this->running = FALSE;

                return;
            }

            $this->setLastExecTime();
            try {
                if ($this->mono_sbas_id) {
                    $sql = 'SELECT sbas_id, task2.* FROM sbas, task2 WHERE task_id=:taskid AND sbas_id=:sbas_id';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute(array(':taskid'  => $this->getID(), ':sbas_id' => $this->mono_sbas_id));
                } else {
                    $sql = 'SELECT sbas_id, task2.* FROM sbas, task2 WHERE task_id = :taskid';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute(array(':taskid' => $this->getID()));
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
                if ( ! $this->running) {
                    break;
                }

                $this->sbas_id = (int) $row['sbas_id'];
                $this->log('This task works now on ' . phrasea::sbas_names($this->sbas_id));

                try {
                    // get the records to process
                    $databox = databox::get_instance((int)$row['sbas_id']);
                } catch (Exception $e) {
                    $this->log(sprintf('Warning : can\' connect to sbas(%s)', $row['sbas_id']));
                    continue;
                }

                try {
                    $this->loadSettings(simplexml_load_string($row['settings']));
                } catch (Exception $e) {
                    $this->log($e->getMessage());
                    continue;
                }

                $process_ret = $this->processSbas($databox);

                // close the cnx to the dbox
                $connbas = $databox->get_connection();
                if ($connbas instanceof PDO) {
                    $connbas->close();
                    unset($connbas);
                }


                switch ($process_ret) {
                    case self::STATE_MAXMEGSREACHED:
                    case self::STATE_MAXRECSDONE:
                        if ($this->getRunner() == self::RUNNER_SCHEDULER) {
                            $this->setState(self::STATE_TORESTART);
                            $this->running = FALSE;
                        }
                        break;

                    case self::STATE_TOSTOP:
                        $this->setState(self::STATE_TOSTOP);
                        $this->running = FALSE;
                        break;

                    case self::STATE_TODELETE: // formal 'suicidable'
                        // DO NOT SUICIDE IN THE LOOP, may have to work on other sbas !!!
                        $task_must_delete = TRUE;
                        break;

                    case self::STATE_OK:
                        break;
                }

                $this->flushRecordsSbas();
            }

            $this->incrementLoops();
            $this->pause($duration);
        }

        if ($task_must_delete) {
            $this->setState(self::STATE_TODELETE);
            $this->log('task will self delete');
        }

        return;
    }

    /**
     *
     * @return <type>
     */
    protected function processSbas(databox $databox)
    {
        $ret = self::STATE_OK;

        try {
            // get the records to process
            $rs = $this->retrieveSbasContent($databox);

            // process the records
            $ret = $this->processLoop($databox, $rs);

        } catch (Exception $e) {
            $this->log('Error  : ' . $e->getMessage());
        }

        return $ret;
    }
}

