<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use collection;
use \databox;
use Doctrine\DBAL\Connection;
use PDO;
use \record_adapter;

class RecordsActionsWorker implements WorkerInterface
{
    private $app;
    /** @var PropertyAccess  */
    private $conf;
    private $logger;
    /** @var WorkerRunningJobRepository */
    private $repoWorker;

    /** @var array */
    private $databoxes;

    public function __construct(PhraseaApplication $app)
    {
        $this->app          = $app;
        $this->conf         = $this->app['conf'];
        $this->logger       = $this->app['alchemy_worker.logger'];
        $this->repoWorker   = $app['repo.worker-running-job'];

        // allow to access databox/collections by id or name
        foreach ($app->getDataboxes() as $databox) {
            $bid = (string)($databox->get_sbas_id());
            $this->databoxes[$bid] = [
                'db' => $databox,
                'collections' => []
            ];
            $this->databoxes[$databox->get_dbname()] = &$this->databoxes[$bid];

            foreach ($databox->get_collections() as $coll) {
                $cid = (string)($coll->get_coll_id());
                $this->databoxes[$bid]['collections'][$cid] = $coll;
                $this->databoxes[$bid]['collections'][$coll->get_name()] = &$this->databoxes[$bid]['collections'][$cid];
            }
        }
    }

    /**
     * @param $idOrName
     * @return databox|null
     */
    private function getDatabox($dbIdOrName)
    {
        $dbIdOrName = (string)$dbIdOrName;
        if(array_key_exists($dbIdOrName, $this->databoxes)) {
            return $this->databoxes[$dbIdOrName]['db'];
        }
        return null;
    }

    /**
     * @param $dbIdOrName
     * @param $collIdOrName
     * @return collection|null
     */
    private function getCollection($dbIdOrName, $collIdOrName)
    {
        $dbIdOrName = (string)$dbIdOrName;
        if (array_key_exists($dbIdOrName, $this->databoxes)) {
            $collIdOrName = (string)$collIdOrName;
            if (array_key_exists($collIdOrName, $this->databoxes[$dbIdOrName]['collections'])) {
                return $this->databoxes[$dbIdOrName]['collections'][$collIdOrName];
            }
        }
        return null;
    }

    public function process(array $payload)
    {
        $xmlSettings = $this->conf->get(['workers', 'records_actions', 'xmlSetting'], null);

        if (empty($xmlSettings)) {
            $this->logger->error("Can't find the xml setting!");

            return 0;
        }
        else {
            $em = $this->repoWorker->getEntityManager();
            $em->beginTransaction();

            try {
                $workerRunningJob = new WorkerRunningJob();
                $workerRunningJob
                    ->setWork(MessagePublisher::RECORDS_ACTIONS_TYPE)
                    ->setPublished(new \DateTime('now'))
                    ->setStatus(WorkerRunningJob::RUNNING)
                ;

                $em->persist($workerRunningJob);

                $em->flush();

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }

            $settings = simplexml_load_string($xmlSettings);
            $tasks = array();
            foreach($settings->tasks->task as $task) {
                $tasks[] = $task;
            }

            try {
                $data = $this->getData($tasks);
                foreach ($data as $record) {
                    $this->processData($record);
                }
            } catch(\Exception $e) {
                $this->logger->error('Exception when processing data: ' . $e->getMessage());

                $workerRunningJob
                    ->setStatus(WorkerRunningJob::ERROR)
                    ->setInfo($e->getMessage())
                    ->setFinished(new \DateTime('now'))
                ;

                $this->repoWorker->reconnect();

                $em->persist($workerRunningJob);

                $em->flush();

                return 0;
            }

            if ($workerRunningJob != null) {
                $workerRunningJob
                    ->setStatus(WorkerRunningJob::FINISHED)
                    ->setFinished(new \DateTime('now'))
                ;

                $this->repoWorker->reconnect();

                $em->persist($workerRunningJob);

                $em->flush();
            }
        }
    }

    private function getData(array $tasks)
    {
        $ret = [];
        foreach ($tasks as $sxtask) {
            $task = $this->calcSQL($sxtask);

            if (!$task['active'] || !$task['sql']) {
                continue;
            }

            $this->logger->info(sprintf("playing task '%s' on base '%s'", $task['name'], $task['basename'] ? $task['basename'] : '<unknown>'));

            $databox = $this->getDatabox($task['databoxId']);
            if(!$databox) {
                $this->logger->error(sprintf("unknown databox %s", $task['databoxId']));
                continue;
            }

            $to_coll = null;
            if (($x = trim((string) ($sxtask->to->coll['id']))) !== "") {
                $coll = $this->getCollection($databox->get_sbas_id(), $x);
                if(!$coll) {
                    $this->logger->error(sprintf("unknown collection %s", $x));
                    continue;
                }
                $to_coll = $coll;
            }

            $stmt = $databox->get_connection()->prepare($task['sql']['real']['sql']);
            $stmt->execute();
            while (false !== $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tmp = [
                    'databoxId'   => $databox->get_sbas_id(),
                    'record_id' => $row['record_id'],
                    'action'    => $task['action']
                ];

                $rec = $databox->get_record($row['record_id']);
                switch ($task['action']) {
                    case 'UPDATE':
                        // change collection ?
                        if($to_coll) {
                            $tmp['coll'] = $coll->get_coll_id();
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
                    case 'TRASH':
                        $ret[] = $tmp;
                        break;
                }
            }
            $stmt->closeCursor();
        }

        return $ret;
    }

    private function processData($row)
    {
        $databox = $this->getDatabox($row['databoxId']);
        if(!$databox) {
            $this->logger->error(sprintf("unknown databox %s", $row['databoxId']));
            return $this;
        }
        $rec = $databox->get_record($row['record_id']);

        switch ($row['action']) {
            case 'UPDATE':
                // change collection ?
                if (array_key_exists('coll', $row)) {
                    $coll = collection::getByCollectionId($this->app, $databox, $row['coll']);
                    $rec->move_to_collection($coll);
                    $this->logger->info(sprintf("on databox %s move recordId %s to coll %s \n", $row['databoxId'], $row['record_id'], $coll->get_coll_id()));
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
                    $this->logger->info(sprintf("on databox %s set recordId %s status to %s \n", $row['databoxId'], $row['record_id'], $status));
                }
                break;

            case 'DELETE':
                if ($row['deletechildren'] && $rec->isStory()) {
                    /** @var record_adapter $child */
                    foreach ($rec->getChildren() as $child) {
                        $child->delete();
                        $this->logger->info(sprintf("on databox %s delete (grp child) recordId %s \n", $row['databoxId'], $child->getRecordId()));
                    }
                }
                $rec->delete();
                $this->logger->info(sprintf("on databox %s delete recordId %s \n", $row['databoxId'], $rec->getRecordId()));
                break;
            case 'TRASH':
                // move to trash collection if exist
                $trashCollection = $databox->getTrashCollection();
                if ($trashCollection != null) {
                    $rec->move_to_collection($trashCollection);
                    $this->logger->info(sprintf("on databox %s move recordId %s to trash.", $row['databoxId'], $row['record_id']));
                    // disable permalinks
                    foreach ($rec->get_subdefs() as $subdef) {
                        if ( ($pl = $subdef->get_permalink()) ) {
                            $pl->set_is_activated(false);
                        }
                    }
                }

                break;
        }

        return $this;
    }

    public function calcSQL($sxtask, $playTest = false)
    {
        $ret = [
            'name'                 => $sxtask['name'] ? (string) $sxtask['name'] : 'sans nom',
            'name_htmlencoded'     => \p4string::MakeString(($sxtask['name'] ? $sxtask['name'] : 'sans nom'), 'html'),
            'active'               => trim($sxtask['active']) === '1',
            'databoxId'            => null,
            'basename'             => '',
            'basename_htmlencoded' => '',
            'action'               => strtoupper($sxtask['action']),
            'sql'                  => null,
            'err'                  => '',
            'err_htmlencoded'      => '',
        ];

        $databox = $this->getDatabox($sxtask['databoxId']);
        if(!$databox) {
            $ret['err'] = sprintf("unknown databox \"%s\"", $sxtask['databoxId']);
            $ret['err_htmlencoded'] = htmlentities($ret['err']);
            $this->logger->error($ret['err']);
            return $ret;
        }

        $ret['databoxId'] = $databoxId = $databox->get_sbas_id();

        $ret['basename'] = $databox->get_label($this->app['locale']);
        $ret['basename_htmlencoded'] = htmlentities($ret['basename']);
        try {
            switch ($ret['action']) {
                case 'UPDATE':
                    $ret['sql'] = $this->calcUPDATE($databoxId, $sxtask, $playTest);
                    break;
                case 'DELETE':
                    $ret['sql'] = $this->calcDELETE($databoxId, $sxtask, $playTest);
                    $ret['deletechildren'] = (int)($sxtask['deletechildren']);
                    break;
                case 'TRASH':
                    if ($databox->getTrashCollection() === null) {
                        $ret['err'] = "trash collection not found on databox = ". $sxtask['databoxId'];
                        $ret['err_htmlencoded'] = htmlentities($ret['err']);
                    } else {
                        // there is no to tag, just from tag
                        // so it's the same as calcDELETE
                        $ret['sql'] = $this->calcDELETE($databoxId, $sxtask, $playTest);
                    }
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

        return $ret;
    }

    private function calcUPDATE($databoxId, &$sxtask, $playTest)
    {
        $tws = array(); // NEGATION of updates, used to build the 'test' sql

        // set coll_id ?
        $to_coll = null;
        if (($x = trim((string) ($sxtask->to->coll['id']))) !== "") {
            $coll = $this->getCollection($databoxId, $x);
            if(!$coll) {
                $ret = array(
                    'real' => array(
                        'sql' => '',
                        'sql_htmlencoded' => '',
                    ),
                    'test' => array(
                        'sql' => '',
                        'sql_htmlencoded' => '',
                        'result' => null,
                        'err' => sprintf("unknown collection %s", $x)
                    )
                );
                $this->logger->error(sprintf("unknown collection %s", $x));
                return $ret;
            }
            $to_coll = $coll;
        }

        if ($to_coll) {
            $tws[] = 'coll_id!=' . $to_coll->get_coll_id();
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
        list($tw, $join, $err) = $this->calcWhere($databoxId, $sxtask);

        if (!empty($err)) {
            throw(new \Exception($err));
        }

        // ... complete the where to build the TEST
        if (count($tws) == 1) {
            $tw[] = $tws[0];
        } elseif (count($tws) > 1) {
            $tw[] = '(' . implode(') OR (', $tws) . ')';
        }

        // build the TEST sql (select)
        $sql_test = 'SELECT record.record_id FROM record' . $join;
        if (count($tw) > 0) {
            $sql_test .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');
        }

        // build the real sql (select)
        $sql_real = 'SELECT record.record_id FROM record' . $join;
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
            $ret['test']['result'] = $this->playTest($databoxId, $sql_test);
        }

        return $ret;
    }

    private function calcDELETE($databoxId, &$sxtask, $playTest)
    {
        // compute the 'where' clause
        list($tw, $join, $err) = $this->calcWhere($databoxId, $sxtask);

        if (!empty($err)) {
            throw(new \Exception($err));
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
            $ret['test']['result'] = $this->playTest($databoxId, $sql_test);
        }

        return $ret;
    }

    private function playTest($databoxId, $sql)
    {
        /** @var databox $databox */
        $databox = $this->app->findDataboxById($databoxId);
        $connbas = $databox->get_connection();
        $result = ['rids' => [], 'err' => '', 'n'   => null];

        $result['n'] = $connbas->query('SELECT COUNT(*) AS n FROM (' . $sql . ') AS x')->fetchColumn();

        $stmt = $connbas->prepare('SELECT record_id FROM (' . $sql . ') AS x LIMIT 10');
        if ($stmt->execute([])) {
            while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
                $result['rids'][] = $row['record_id'];
            }
            $stmt->closeCursor();
        } else {
            $result['err'] = $connbas->errorInfo();
        }

        return $result;
    }

    private function calcWhere($databoxId, &$sxtask)
    {
        $err = "";
        /** @var databox $databox */
        $databox = $this->app->findDataboxById($databoxId);
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
            if ($field != null) {
                $ijoin++;
                $comp = trim($x['compare']);
                if (in_array($comp, array('<', '>', '<=', '>=', '=', '!='))) {
                    $s = 'p' . $ijoin . '.meta_struct_id=' . $connbas->quote($field->get_id()) . ' AND p' . $ijoin . '.value' . $comp . $connbas->quote($x['value']);

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

        // criteria <number field="XXX" compare="OP" value="ZZZ" />
        foreach ($sxtask->from->number as $x) {
            $field = $x['field'];
            $value = (double)($x['value']);
            switch ($field) {
                case "#filesize":
                    $comp = trim($x['compare']);
                    if (in_array($comp, array('<', '>', '<=', '>=', '=', '!='))) {
                        $ijoin++;
                        $tw[] = sprintf(
                            'p%d.name=%s AND p%d.size%s%s',
                            $ijoin,
                            $connbas->quote('document'),
                            $ijoin,
                            $comp,
                            $value
                        );
                        $join .= sprintf(' INNER JOIN subdef AS p%d USING(record_id)', $ijoin);
                    } else {
                        // bad comparison operator
                        $err .= sprintf("bad comparison operator (%s)\n", $comp);
                    }
                    break;
                default:
                    $field = $struct->get_element_by_name($x['field']);
                    if ($field != null) {
                        $value = (double)($x['value']);
                        $ijoin++;
                        $comp = trim($x['compare']);
                        if (in_array($comp, array('<', '>', '<=', '>=', '=', '!='))) {
                            $ijoin++;
                            $tw[] = sprintf(
                                'p%d.meta_struct_id=%s AND CAST(p%d.value AS DECIMAL)%s%s',
                                $ijoin,
                                $connbas->quote($field->get_id()),
                                $ijoin,
                                $comp,
                                $value
                            );
                            $join .= sprintf(' INNER JOIN metadatas AS p%d USING(record_id)', $ijoin);
                        } else {
                            // bad comparison operator
                            $err .= sprintf("bad comparison operator (%s)\n", $comp);
                        }
                    } else {
                        // unknown field ?
                        $err .= sprintf("unknown field (%s)\n", $x['field']);
                    }
                    break;
            }

        }

        // criteria <is_set field="XXX" />
        foreach ($sxtask->from->is_set as $x) {
            $field = $struct->get_element_by_name($x['field']);
            if ($field != null) {
                $ijoin++;
                $s = 'p' . $ijoin . '.meta_struct_id=' . $connbas->quote($field->get_id())  ;
                $tw[] = $s;
                $join .= ' INNER JOIN metadatas AS p' . $ijoin . ' USING(record_id)';
            } else {
                // unknown field ?
                $err .= sprintf("unknown field (%s)\n", $x['field']);
            }
        }

        // criteria <is_unset field="XXX" />
        foreach ($sxtask->from->is_unset as $x) {
            $field = $struct->get_element_by_name($x['field']);
            if ($field != null) {
                $ijoin++;
                $s = 'ISNULL(p' . $ijoin . '.id)' ;
                $tw[] = $s;
                $join .= ' LEFT JOIN metadatas AS p' . $ijoin . ' ON(record.record_id = p'.$ijoin.'.record_id AND p' . $ijoin . '.meta_struct_id=' . $connbas->quote($field->get_id()) . ')';
            } else {
                // unknown field ?
                $err .= sprintf("unknown field (%s)\n", $x['field']);
            }
        }

        // criteria <date direction ="XXX" field="YYY" delta="Z period" />
        foreach ($sxtask->from->date as $x) {
            $dir = strtoupper(trim($x['direction']));
            $delta = strtoupper(trim($x['delta']));
            $unit = "DAY";
            $matches = [];
            if($delta === "") {
                $delta = 0;
            }
            else {
                if (preg_match('/^([-+]?\d+)(\s+(HOUR|DAY|WEEK|MONTH|YEAR)S?)?$/', $delta, $matches) === 1) {
                    if (count($matches) === 4) {
                        $delta = (int)($matches[1]);
                        $unit = $matches[3];
                    }
                    else if (count($matches) === 2) {
                        $delta = (int)($matches[1]);
                    }
                    else {
                        $err .= sprintf("bad delta (%s)\n", $x['delta']);
                        continue;
                    }
                }
                else {
                    $err .= sprintf("bad delta (%s)\n", $x['delta']);
                    continue;
                }
            }

            switch ($x['field']) {
                case '#moddate':
                case '#credate':
                    $s = 'NOW()';
                    $dbField = substr($x['field'], 1);
                    if (in_array($dir, array('BEFORE', 'AFTER'))) {
                        // prevent malformed dates to act
                        $tw[] = '!ISNULL(CAST('. $dbField . ' AS DATETIME))';
                        $s .= ($dir == 'BEFORE') ? '<' : '>=';

                        if ($delta > 0) {
                            $s .= '(' . $dbField . '+INTERVAL ' . $delta . ' ' . $unit . ')';
                        } elseif ($delta < 0) {
                            $s .= '(' . $dbField . '-INTERVAL ' . -$delta . ' ' . $unit . ')';
                        } else {
                            $s .= 'CAST(' . $dbField . ' AS DATETIME)';
                        }
                    } else {
                        // bad direction
                        $err .= sprintf("bad direction (%s)\n", $x['direction']);
                    }
                    $tw[] = $s;

                    break;
                default:
                    $field = $struct->get_element_by_name($x['field']);
                    if ($field != null) {
                        $ijoin++;
                        $s = 'p' . $ijoin . '.meta_struct_id=' . $connbas->quote($field->get_id()) . ' AND NOW()';
                        if (in_array($dir, array('BEFORE', 'AFTER'))) {
                            // prevent malformed dates to act
                            $tw[] = '!ISNULL(CAST(p' . $ijoin . '.value AS DATETIME))';
                            $s .= ($dir == 'BEFORE') ? '<' : '>=';

                            if ($delta > 0) {
                                $s .= '(p' . $ijoin . '.value+INTERVAL ' . $delta . ' ' . $unit . ')';
                            } elseif ($delta < 0) {
                                $s .= '(p' . $ijoin . '.value-INTERVAL ' . -$delta . ' ' . $unit . ')';
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

                    break;
            }
        }

        // criteria <coll compare="OP" id="X,Y,Z" />
        if (($x = $sxtask->from->coll) ) {
            $tcoll = explode(',', $x['id']);
            foreach ($tcoll as $i => $c) {
                $coll = $this->getCollection($databoxId, $c);
                if(!$coll) {
                    $err .= sprintf("unknown collection %s", $x['id']);
                }
                else {
                    $tcoll[$i] = $coll->get_coll_id();
                }
            }
            if(count($tcoll) > 0) {
                if ($x['compare'] == '=') {
                    if (count($tcoll) == 1) {
                        $tw[] = 'coll_id = ' . $tcoll[0];
                    }
                    else {
                        $tw[] = 'coll_id IN(' . implode(',', $tcoll) . ')';
                    }
                }
                elseif ($x['compare'] == '!=') {
                    if (count($tcoll) == 1) {
                        $tw[] = 'coll_id != ' . $tcoll[0];
                    }
                    else {
                        $tw[] = 'coll_id NOT IN(' . implode(',', $tcoll) . ')';
                    }
                }
                else {
                    // bad operator
                    $err .= sprintf("bad comparison operator (%s)\n", $x['compare']);
                }
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
