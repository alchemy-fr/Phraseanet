<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\TaskManager\Editor\RecordMoverEditor;
use \databox;
use Doctrine\DBAL\Connection;
use record_adapter;

class RecordMoverJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans("Record Mover");
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'RecordMover';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans("Moves records");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new RecordMoverEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();

        $settings = simplexml_load_string($data->getTask()->getSettings());
        $logsql = (Boolean) $settings->logsql;
        $tasks = array();
        foreach($settings->tasks->task as $task) {
            $tasks[] = $task;
        }

        $data = $this->getData($app, $tasks, $logsql);

        foreach ($data as $record) {
            $this->processData($app, $record, $logsql);
        }
    }

    private function processData(Application $app, $row, $logsql)
    {
        $databox = $app->findDataboxById($row['sbas_id']);
        $rec = $databox->get_record($row['record_id']);

        switch ($row['action']) {
            case 'UPDATE':
                // change collection ?
                if (array_key_exists('coll', $row)) {
                    $coll = \collection::getByCollectionId($app, $databox, $row['coll']);
                    $rec->move_to_collection($coll);
                    if ($logsql) {
                        $this->log('debug', sprintf("on sbas %s move rid %s to coll %s \n", $row['sbas_id'], $row['record_id'], $coll->get_coll_id()));
                    }
                }

                // change sb ?
                if (array_key_exists('sb', $row)) {
                    $status = str_split($rec->getStatus());
                    foreach (str_split(strrev($row['sb'])) as $bit => $val) {
                        if ($val == '0' || $val == '1') {
                            $status[31 - $bit] = $val;
                        }
                    }
                    $status = implode('', $status);
                    $rec->setStatus($status);
                    if ($logsql) {
                        $this->log('debug', sprintf("on sbas %s set rid %s status to %s \n", $row['sbas_id'], $row['record_id'], $status));
                    }
                }
                break;

            case 'DELETE':
                if ($row['deletechildren'] && $rec->isStory()) {
                    /** @var record_adapter $child */
                    foreach ($rec->getChildren() as $child) {
                        $child->delete();
                        if ($logsql) {
                            $this->log('debug', sprintf("on sbas %s delete (grp child) rid %s \n", $row['sbas_id'], $child->getRecordId()));
                        }
                    }
                }
                $rec->delete();
                if ($logsql) {
                    $this->log('debug', sprintf("on sbas %s delete rid %s \n", $row['sbas_id'], $rec->getRecordId()));
                }
                break;
        }

        return $this;
    }

    private function getData(Application $app, array $tasks, $logsql)
    {
        $ret = [];
        foreach ($tasks as $sxtask) {
            if (!$this->isStarted()) {
                break;
            }

            $task = $this->calcSQL($app, $sxtask);

            if (!$task['active'] || !$task['sql']) {
                continue;
            }

            if ($logsql) {
                $this->log('debug', sprintf("playing task '%s' on base '%s'", $task['name'], $task['basename'] ? $task['basename'] : '<unknown>'));
            }

            try {
                /** @var databox $databox */
                $databox = $app->findDataboxById($task['sbas_id']);
            } catch (\Exception $e) {
                $this->log('error', sprintf("can't connect sbas %s", $task['sbas_id']));
                continue;
            }

            $stmt = $databox->get_connection()->prepare($task['sql']['real']['sql']);
            $stmt->execute();
            while ($this->isStarted() && false !== $row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $tmp = [
                    'sbas_id'   => $task['sbas_id'],
                    'record_id' => $row['record_id'],
                    'action'    => $task['action']
                ];

                $rec = $databox->get_record($row['record_id']);
                switch ($task['action']) {
                    case 'UPDATE':
                        // change collection ?
                        if (($x = (int) ($sxtask->to->coll['id'])) > 0) {
                            $tmp['coll'] = $x;
                        }
                        // change sb ?
                        if (($x = $sxtask->to->status['mask'])) {
                            $tmp['sb'] = $x;
                        }
                        $ret[] = $tmp;
                        break;
                    case 'DELETE':
                        $tmp['deletechildren'] = false;
                        if ($sxtask['deletechildren'] && $rec->isStory()) {
                            $tmp['deletechildren'] = true;
                        }
                        $ret[] = $tmp;
                        break;
                }
            }
            $stmt->closeCursor();
        }

        return $ret;
    }

    public function calcSQL(Application $app, $sxtask, $playTest = false)
    {
        $sbas_id = (int) $sxtask['sbas_id'];

        $ret = [
            'name'                 => $sxtask['name'] ? (string) $sxtask['name'] : 'sans nom',
            'name_htmlencoded'     => \p4string::MakeString(($sxtask['name'] ? $sxtask['name'] : 'sans nom'), 'html'),
            'active'               => trim($sxtask['active']) === '1',
            'sbas_id'              => $sbas_id,
            'basename'             => '',
            'basename_htmlencoded' => '',
            'action'               => strtoupper($sxtask['action']),
            'sql'                  => null,
            'err'                  => '',
            'err_htmlencoded'      => '',
        ];

        try {
            /** @var databox $dbox */
            $dbox = $app->findDataboxById($sbas_id);

            $ret['basename'] = $dbox->get_label($app['locale']);
            $ret['basename_htmlencoded'] = htmlentities($ret['basename']);
            try {
                switch ($ret['action']) {
                    case 'UPDATE':
                        $ret['sql'] = $this->calcUPDATE($app, $sbas_id, $sxtask, $playTest);
                        break;
                    case 'DELETE':
                        $ret['sql'] = $this->calcDELETE($app, $sbas_id, $sxtask, $playTest);
                        $ret['deletechildren'] = (int)($sxtask['deletechildren']);
                        break;
                    default:
                        $ret['err'] = "bad action '" . $ret['action'] . "'";
                        $ret['err_htmlencoded'] = htmlentities($ret['err']);
                        break;
                }
            } catch (\Exception $e) {
                $ret['err'] = $e->getMessage();
                $ret['err_htmlencoded'] = htmlentities($e->getMessage());
            }
        } catch (\Exception $e) {
            $ret['err'] = "bad sbas '" . $sbas_id . "'";
            $ret['err_htmlencoded'] = htmlentities($ret['err']);
        }

        return $ret;
    }

    private function calcUPDATE(Application $app, $sbas_id, &$sxtask, $playTest)
    {
        $tws = array(); // NEGATION of updates, used to build the 'test' sql

        // set coll_id ?
        if (($x = (int) ($sxtask->to->coll['id'])) > 0) {
            $tws[] = 'coll_id!=' . $x;
        }

        // set status ?
        $x = trim($sxtask->to->status['mask']);
        $x = preg_replace('/[^0-1]/', 'x', $x);

        $mx = str_replace(' ', '0', ltrim(str_replace(array('0', 'x'), array(' ', ' '), $x)));
        $ma = str_replace(' ', '0', ltrim(str_replace(array('x', '0'), array(' ', '1'), $x)));
        if ($mx && $ma) {
            $tws[] = '((status ^ 0b' . $mx . ') & 0b' . $ma . ')!=0';
        }
        elseif ($mx) {
            $tws[] = '(status ^ 0b' . $mx . ')!=0';
        }
        elseif ($ma) {
            $tws[] = '(status & 0b' . $ma . ')!=0';
        }

        // compute the 'where' clause
        list($tw, $join, $err) = $this->calcWhere($app, $sbas_id, $sxtask);

        if(!empty($err)) {
            throw(new \Exception($err));
        }

        // ... complete the where to build the TEST
        if (count($tws) == 1) {
            $tw[] = $tws[0];
        } elseif (count($tws) > 1) {
            $tw[] = '(' . implode(') OR (', $tws) . ')';
        }

        // build the TEST sql (select)
        $sql_test = 'SELECT record_id FROM record' . $join;
        if (count($tw) > 0) {
            $sql_test .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');
        }

        // build the real sql (select)
        $sql_real = 'SELECT record_id FROM record' . $join;
        if (count($tw) > 0) {
            $sql_real .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');
        }

        $ret = array(
            'real' => array(
                'sql' => $sql_real,
                'sql_htmlencoded' => htmlentities($sql_real),
            ),
            'test' => array(
                'sql' => $sql_test,
                'sql_htmlencoded' => htmlentities($sql_test),
                'result' => null,
                'err' => null
            )
        );

        if ($playTest) {
            $ret['test']['result'] = $this->playTest($app, $sbas_id, $sql_test);
        }

        return $ret;
    }

    private function calcDELETE(Application $app, $sbas_id, &$sxtask, $playTest)
    {
        // compute the 'where' clause
        list($tw, $join, $err) = $this->calcWhere($app, $sbas_id, $sxtask);

        if(!empty($err)) {
            throw(new \Exception($err));
        }

        // build the TEST sql (select)
        $sql_test = 'SELECT SQL_CALC_FOUND_ROWS record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql_test .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');
        $sql_test .= ' LIMIT 10';

        // build the real sql (select)
        $sql_real = 'SELECT record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql_real .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');

        $ret = [
            'real' => [
                'sql' => $sql_real,
                'sql_htmlencoded' => htmlentities($sql_real),
            ],
            'test' => [
                'sql' => $sql_test,
                'sql_htmlencoded' => htmlentities($sql_test),
                'result' => null,
                'err' => null
            ]
        ];

        if ($playTest) {
            $ret['test']['result'] = $this->playTest($app, $sbas_id, $sql_test);
        }

        return $ret;
    }

    private function playTest(Application $app, $sbas_id, $sql)
    {
        /** @var databox $databox */
        $databox = $app->findDataboxById($sbas_id);
        $connbas = $databox->get_connection();
        $result = ['rids' => [], 'err' => '', 'n'   => null];

        $result['n'] = $connbas->query('SELECT COUNT(*) AS n FROM (' . $sql . ') AS x')->fetchColumn();

        $stmt = $connbas->prepare('SELECT record_id FROM (' . $sql . ') AS x LIMIT 10');
        if ($stmt->execute([])) {
            while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
                $result['rids'][] = $row['record_id'];
            }
            $stmt->closeCursor();
        } else {
            $result['err'] = $connbas->errorInfo();
        }

        return $result;
    }

    private function calcWhere(Application $app, $sbas_id, &$sxtask)
    {
        $err = "";
        /** @var databox $databox */
        $databox = $app->findDataboxById($sbas_id);
        /** @var Connection $connbas */
        $connbas = $databox->get_connection();

        $struct = $databox->get_meta_structure();

        $tw = array();
        $join = '';

        $ijoin = 0;

        // criteria <type type="XXX" />
        if (($x = $sxtask->from->type['type']) !== null) {
            switch (strtoupper($x)) {
                case 'RECORD':
                    $tw[] = 'parent_record_id!=record_id';
                    break;
                case 'STORY':
                    $tw[] = 'parent_record_id=record_id';
                    break;
            }
        }

        // criteria <text field="XXX" compare="OP" value="ZZZ" />
        foreach ($sxtask->from->text as $x) {
            $field = $struct->get_element_by_name($x['field']);
            if($field != null) {
                $ijoin++;
                $comp = trim($x['compare']);
                if (in_array($comp, array('<', '>', '<=', '>=', '=', '!='))) {
                    $s = 'p' . $ijoin . '.meta_struct_id=' . $connbas->quote($field->get_id()) . ' AND p' . $ijoin . '.value' . $comp
                        . '' . $connbas->quote($x['value']) . '';

                    $tw[] = $s;
                    $join .= ' INNER JOIN metadatas AS p' . $ijoin . ' USING(record_id)';
                } else {
                    // bad comparison operator
                    $err .= sprintf("bad comparison operator (%s)\n", $comp);
                }
            } else {
                // unknown field ?
                $err .= sprintf("unknown field (%s)\n", $x['field']);
            }
        }

        // criteria <date direction ="XXX" field="YYY" delta="Z" />
        foreach ($sxtask->from->date as $x) {
            $field = $struct->get_element_by_name($x['field']);
            if($field != null) {
                $ijoin++;
                $s = 'p' . $ijoin . '.meta_struct_id=' . $connbas->quote($field->get_id()) . ' AND NOW()';
                $dir = strtoupper($x['direction']);
                if (in_array($dir, array('BEFORE', 'AFTER'))) {
                    // prevent malformed dates to act
                    $tw[] = '!ISNULL(CAST(p' . $ijoin . '.value AS DATETIME))';
                    $s .= $dir == 'BEFORE' ? '<' : '>=';
                    $delta = (int)($x['delta']);
                    if ($delta > 0) {
                        $s .= '(p' . $ijoin . '.value+INTERVAL ' . $delta . ' DAY)';
                    } elseif ($delta < 0) {
                        $s .= '(p' . $ijoin . '.value-INTERVAL ' . -$delta . ' DAY)';
                    } else {
                        $s .= 'CAST(p' . $ijoin . '.value AS DATETIME)';
                    }

                    $tw[] = $s;
                    $join .= ' INNER JOIN metadatas AS p' . $ijoin . ' USING(record_id)';
                } else {
                    // bad direction
                    $err .= sprintf("bad direction (%s)\n", $x['direction']);
                }
            }
            else {
                // unknown field ?
                $err .= sprintf("unknown field (%s)\n", $x['field']);
            }
        }

        // criteria <coll compare="OP" id="X,Y,Z" />
        if (($x = $sxtask->from->coll) ) {
            $tcoll = explode(',', $x['id']);
            foreach ($tcoll as $i => $c) {
                $tcoll[$i] = (int)$c;
            }
            if ($x['compare'] == '=') {
                if (count($tcoll) == 1) {
                    $tw[] = 'coll_id = ' . $tcoll[0];
                } else {
                    $tw[] = 'coll_id IN(' . implode(',', $tcoll) . ')';
                }
            } elseif ($x['compare'] == '!=') {
                if (count($tcoll) == 1) {
                    $tw[] = 'coll_id != ' . $tcoll[0];
                } else {
                    $tw[] = 'coll_id NOT IN(' . implode(',', $tcoll) . ')';
                }
            } else {
                // bad operator
                $err .= sprintf("bad comparison operator (%s)\n", $x['compare']);
            }
        }

        // criteria <status mask="XXXXX" />
        $x = trim($sxtask->from->status['mask']);
        $x = preg_replace('/[^0-1]/', 'x', $x);

        $mx = str_replace(' ', '0', ltrim(str_replace(array('0', 'x'), array(' ', ' '), $x)));
        $ma = str_replace(' ', '0', ltrim(str_replace(array('x', '0'), array(' ', '1'), $x)));
        if ($mx && $ma) {
            $tw[] = '((status ^ 0b'. $mx . ') & 0b'. $ma . ')=0';
        } elseif ($mx) {
            $tw[] = '(status ^ 0b' . $mx . ')=0';
        } elseif ($ma) {
            $tw[] = '(status & 0b' . $ma . ")=0";
        }

        return array($tw, $join, $err);
    }
}
