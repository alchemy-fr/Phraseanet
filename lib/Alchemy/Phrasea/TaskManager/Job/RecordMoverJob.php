<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\TaskManager\Editor\RecordMoverEditor;

class RecordMoverJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return _("Record Mover");
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
        return _("Moves records");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new RecordMoverEditor();
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $task = $data->getTask();

        $settings = $task->getSettings();
        $logsql = (Boolean) $settings->logsql;
        $tasks = (array) $settings->tasks;

        $data = $this->getData($app, $tasks, $logsql);

        foreach ($data as $record) {
            $this->processData($app, $record, $logsql);
        }
    }

    private function processData(Application $app, $row, $logsql)
    {
        $databox = $app['phraseanet.appbox']->get_databox($row['sbas_id']);
        $rec = $databox->get_record($row['record_id']);

        switch ($row['action']) {
            case 'UPDATE':
                // change collection ?
                if (array_key_exists('coll', $row)) {
                    $coll = \collection::get_from_coll_id($app, $databox, $row['coll']);
                    $rec->move_to_collection($coll, $app['phraseanet.appbox']);
                    $this['phraseanet.SE']->updateRecord($rec);
                    if ($logsql) {
                        $this->log('debug', sprintf("on sbas %s move rid %s to coll %s \n", $row['sbas_id'], $row['record_id'], $coll->get_coll_id()));
                    }
                }

                // change sb ?
                if (array_key_exists('sb', $row)) {
                    $status = str_split($rec->get_status());
                    foreach (str_split(strrev($row['sb'])) as $bit => $val) {
                        if ($val == '0' || $val == '1') {
                            $status[31 - $bit] = $val;
                        }
                    }
                    $rec->set_binary_status(implode('', $status));
                    $app['phraseanet.SE']->updateRecord($rec);
                    if ($logsql) {
                        $this->log('debug', sprintf("on sbas %s set rid %s status to %s \n", $row['sbas_id'], $row['record_id'], $status));
                    }
                }
                break;

            case 'DELETE':
                if ($row['deletechildren'] && $rec->is_grouping()) {
                    foreach ($rec->get_children() as $child) {
                        $child->delete();
                        $app['phraseanet.SE']->removeRecord($child);
                        if ($logsql) {
                            $this->log('debug', sprintf("on sbas %s delete (grp child) rid %s \n", $row['sbas_id'], $child->get_record_id()));
                        }
                    }
                }
                $rec->delete();
                $app['phraseanet.SE']->removeRecord($rec);
                if ($logsql) {
                    $this->log('debug', sprintf("on sbas %s delete rid %s \n", $row['sbas_id'], $rec->get_record_id()));
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

            $task = $this->calcSQL($sxtask);

            if (!$task['active']) {
                continue;
            }

            if ($logsql) {
                $this->log('debug', sprintf("playing task '%s' on base '%s'", $task['name'], $task['basename'] ? $task['basename'] : '<unknown>'));
            }

            try {
                $databox = $app['phraseanet.appbox']->get_databox($task['sbas_id']);
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
                        if ($sxtask['deletechildren'] && $rec->is_grouping()) {
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
            $dbox = $app['phraseanet.appbox']->get_databox($sbas_id);

            $ret['basename'] = $dbox->get_label($app['locale.I18n']);
            $ret['basename_htmlencoded'] = htmlentities($ret['basename']);
            switch ($ret['action']) {
                case 'UPDATE':
                    $ret['sql'] = $this->calcUPDATE($app, $sbas_id, $sxtask, $playTest);
                    break;
                case 'DELETE':
                    $ret['sql'] = $this->calcDELETE($app, $sbas_id, $sxtask, $playTest);
                    $ret['deletechildren'] = (int) ($sxtask['deletechildren']);
                    break;
                default:
                    $ret['err'] = "bad action '" . $ret['action'] . "'";
                    $ret['err_htmlencoded'] = htmlentities($ret['err']);
                    break;
            }
        } catch (\Exception $e) {
            $ret['err'] = "bad sbas '" . $sbas_id . "'";
            $ret['err_htmlencoded'] = htmlentities($ret['err']);
        }

        return $ret;
    }

    private function calcUPDATE(Application $app, $sbas_id, &$sxtask, $playTest)
    {
        $tws = []; // NEGATION of updates, used to build the 'test' sql
        //
        // set coll_id ?
        if (($x = (int) ($sxtask->to->coll['id'])) > 0) {
            $tws[] = 'coll_id!=' . $x;
        }

        // set status ?
        $x = $sxtask->to->status['mask'];
        $mx = str_replace(' ', '0', ltrim(str_replace(['0', 'x'], [' ', ' '], $x)));
        $ma = str_replace(' ', '0', ltrim(str_replace(['x', '0'], [' ', '1'], $x)));
        if ($mx && $ma)
            $tws[] = '((status ^ 0b' . $mx . ') & 0b' . $ma . ')!=0';
        elseif ($mx)
            $tws[] = '(status ^ 0b' . $mx . ')!=0';
        elseif ($ma)
            $tws[] = '(status & 0b' . $ma . ')!=0';

        // compute the 'where' clause
        list($tw, $join) = $this->calcWhere($app, $sbas_id, $sxtask);

        // ... complete the where to buid the TEST
        if (count($tws) == 1)
            $tw[] = $tws[0];
        elseif (count($tws) > 1)
            $tw[] = '(' . implode(') OR (', $tws) . ')';

        // build the TEST sql (select)
        $sql_test = 'SELECT record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql_test .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');

        // build the real sql (select)
        $sql = 'SELECT record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');

        $ret = [
            'real' => [
                'sql'             => $sql,
                'sql_htmlencoded' => htmlentities($sql),
            ],
            'test'            => [
                'sql'             => $sql_test,
                'sql_htmlencoded' => htmlentities($sql_test),
                'result'          => NULL,
                'err'             => NULL
            ]
        ];

        if ($playTest) {
            $ret['test']['result'] = $this->playTest($app, $sbas_id, $sql_test);
        }

        return $ret;
    }

    private function calcDELETE(Application $app, $sbas_id, &$sxtask, $playTest)
    {
        // compute the 'where' clause
        list($tw, $join) = $this->calcWhere($app, $sbas_id, $sxtask);

        // build the TEST sql (select)
        $sql_test = 'SELECT SQL_CALC_FOUND_ROWS record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql_test .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');
        $sql_test .= ' LIMIT 10';

        // build the real sql (select)
        $sql = 'SELECT record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');

        $ret = [
            'real' => [
                'sql'             => $sql,
                'sql_htmlencoded' => htmlentities($sql),
            ],
            'test'            => [
                'sql'             => $sql_test,
                'sql_htmlencoded' => htmlentities($sql_test),
                'result'          => NULL,
                'err'             => NULL
            ]
        ];

        if ($playTest) {
            $ret['test']['result'] = $this->playTest($app, $sbas_id, $sql_test);
        }

        return $ret;
    }

    private function playTest(Application $app, $sbas_id, $sql)
    {
        $connbas = \connection::getPDOConnection($app, $sbas_id);
        $result = ['rids' => [], 'err' => '', 'n'   => null];

        $result['n'] = $connbas->query('SELECT COUNT(*) AS n FROM (' . $sql . ') AS x')->fetchColumn();

        $stmt = $connbas->prepare('SELECT record_id FROM (' . $sql . ') AS x LIMIT 10');
        if ($stmt->execute([])) {
            while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
                $result['rids'][] = $row['record_id'];
            }
            $stmt->closeCursor();
        } else {
            $result['err'] = $connbas->last_error();
        }

        return $result;
    }

    private function calcWhere(Application $app, $sbas_id, &$sxtask)
    {
        $connbas = \connection::getPDOConnection($app, $sbas_id);

        $tw = [];
        $join = '';

        $ijoin = 0;

        // criteria <type type="XXX" />
        if (($x = $sxtask->from->type['type']) !== NULL) {
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
            $ijoin++;
            $comp = strtoupper($x['compare']);
            if (in_array($comp, ['<', '>', '<=', '>=', '=', '!='])) {
                $s = 'p' . $ijoin . '.name=\'' . $x['field'] . '\' AND p' . $ijoin . '.value' . $comp;
                $s .= '' . $connbas->quote($x['value']) . '';

                $tw[] = $s;
                $join .= ' INNER JOIN prop AS p' . $ijoin . ' USING(record_id)';
            } else {
                // bad comparison operator
            }
        }

        // criteria <date direction ="XXX" field="YYY" delta="Z" />
        foreach ($sxtask->from->date as $x) {
            $ijoin++;
            $s = 'p' . $ijoin . '.name=\'' . $x['field'] . '\' AND NOW()';
            $s .= strtoupper($x['direction']) == 'BEFORE' ? '<' : '>=';
            $delta = (int) ($x['delta']);
            if ($delta > 0)
                $s .= '(p' . $ijoin . '.value+INTERVAL ' . $delta . ' DAY)';
            elseif ($delta < 0)
                $s .= '(p' . $ijoin . '.value-INTERVAL ' . -$delta . ' DAY)';
            else
                $s .= 'p' . $ijoin . '.value';

            $tw[] = $s;
            $join .= ' INNER JOIN prop AS p' . $ijoin . ' USING(record_id)';
        }

        // criteria <coll compare="OP" id="X,Y,Z" />
        if (($x = $sxtask->from->coll) !== NULL) {
            $tcoll = explode(',', $x['id']);
            foreach ($tcoll as $i => $c)
                $tcoll[$i] = (int) $c;
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
            }
        }

        // criteria <status mask="XXXXX" />
        $x = $sxtask->from->status['mask'];
        $mx = str_replace(' ', '0', ltrim(str_replace(['0', 'x'], [' ', ' '], $x)));
        $ma = str_replace(' ', '0', ltrim(str_replace(['x', '0'], [' ', '1'], $x)));
        if ($mx && $ma) {
            $tw[] = '((status^0b' . $mx . ')&0b' . $ma . ')=0';
        } elseif ($mx) {
            $tw[] = '(status^0b' . $mx . ')=0';
        } elseif ($ma) {
            $tw[] = '(status&0b' . $ma . ")=0";
        }

        return [$tw, $join];
    }
}
