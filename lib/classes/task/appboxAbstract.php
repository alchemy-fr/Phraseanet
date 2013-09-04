<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class task_appboxAbstract extends task_abstract
{

    abstract protected function retrieveContent(appbox $appbox);

    abstract protected function processOneContent(appbox $appbox, $row);

    abstract protected function postProcessOneContent(appbox $appbox, $row);

    protected function run2()
    {
        $this->running = TRUE;
        while ($this->running) {
            try {
                $conn = connection::getPDOConnection($this->dependencyContainer);
            } catch (Exception $e) {
                $this->log($e->getMessage());
                if ($this->getRunner() == self::RUNNER_SCHEDULER) {
                    $this->log(("Warning : abox connection lost, restarting in 10 min."));

                    $this->sleep(60 * 10);
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
                $sql = 'SELECT settings FROM task2 WHERE task_id = :taskid';
                $stmt = $conn->prepare($sql);
                $stmt->execute(array(':taskid' => $this->getID()));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                $this->records_done = 0;
                $duration = time();
            } catch (Exception $e) {
                // failed sql, simply return
                $this->running = FALSE;

                return;
            }

            if ($row) {
                if (! $this->running) {
                    break;
                }

                try {
                    $this->loadSettings(simplexml_load_string($row['settings']));
                } catch (Exception $e) {
                    $this->log($e->getMessage());
                    continue;
                }

                $process_ret = $this->process($this->dependencyContainer['phraseanet.appbox']);

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
                        $this->setState(self::STATE_TODELETE);
                        $this->running = FALSE;
                        break;
                }
            } // if(row)

            $this->incrementLoops();

            if ($this->running) {
                $this->pause($duration);
            }
        } // while running

        return;
    }

    /**
     *
     * @return <type>
     */
    protected function process(appbox $appbox)
    {
        $ret = self::STATE_OK;

        try {
            // get the records to process
            $rs = $this->retrieveContent($appbox);

            // process the records
            $ret = $this->processLoop($appbox, $rs);
        } catch (Exception $e) {
            $this->log('Error  : ' . $e->getMessage());
        }

        return $ret;
    }
}
