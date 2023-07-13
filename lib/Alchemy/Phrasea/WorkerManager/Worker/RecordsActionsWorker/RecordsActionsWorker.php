<?php

namespace Alchemy\Phrasea\WorkerManager\Worker\RecordsActionsWorker;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Alchemy\Phrasea\WorkerManager\Worker\WorkerInterface;
use collection;
use databox;
use Exception;
use PDO;
use Psr\Log\LoggerInterface;
use record_adapter;
use SimpleXMLElement;

class RecordsActionsWorker implements WorkerInterface
{
    /** @var PhraseaApplication  */
    private $app;

    /** @var PropertyAccess  */
    private $conf;

    /** @var LoggerInterface  */
    private $logger;

    /** @var WorkerRunningJobRepository */
    private $repoWorker;

    /** @var GetByIdOrNameHelper  */
    private $getByIdOrNameHelper;

    public function __construct(PhraseaApplication $app)
    {
        $this->app          = $app;
        $this->conf         = $this->app['conf'];
        $this->logger       = $this->app['alchemy_worker.logger'];
        $this->repoWorker   = $app['repo.worker-running-job'];
        $this->getByIdOrNameHelper = new GetByIdOrNameHelper($app);
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
            } catch (Exception $e) {
                $em->rollback();
            }

            $settings = simplexml_load_string($xmlSettings);
            $tasks = array();
            foreach($settings->tasks->task as $task) {
                $tasks[] = $task;
            }

            try {
                // process will act on db, so we first fetch all...
                $data = $this->getData($tasks);
                // ... then process
                foreach ($data as $record) {
                    $this->processData($record);
                }
            } catch(Exception $e) {
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

        /** @var SimpleXMLElement $sxtask */
        foreach ($tasks as $sxtask) {
            $task = $this->calcSQL($sxtask);

            if (!$task['active'] || !$task['sql']) {
                continue;
            }

            $this->logger->info(sprintf("playing task '%s' on base '%s'", $task['name'], $task['basename'] ? $task['basename'] : '<unknown>'));

            $databox = $this->getByIdOrNameHelper->getDatabox($task['databoxId']);
            if(!$databox) {
                $this->logger->error(sprintf("unknown databox %s", $task['databoxId']));
                continue;
            }

            $to_coll = null;
            if (($x = trim((string) ($sxtask->then->coll['id']))) !== "") {
                $coll = $this->getByIdOrNameHelper->getCollection($databox->get_sbas_id(), $x);
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
                    'action'    => $task['action'],
                    'dry'       => $task['dry'],
                    'set' => [],        // fields to set, with k=name
                ];

                $rec = $databox->get_record($row['record_id']);
                switch ($task['action']) {
                    case 'UPDATE':
                        // change collection ?
                        if($to_coll) {
                            $tmp['coll'] = $to_coll->get_coll_id();
                        }
                        // change sb ?
                        if (($x = $sxtask->then->status['mask'])) {
                            $tmp['sb'] = $x;
                        }
                        // set field(s) ?
                        foreach($sxtask->then->set_field as $x) {
                            $field = trim($x['field']);
                            $value = (string)$x['value'];
                            if(substr($value, 0, 1) === '$') {
                                // ref to selected field
                                $k = substr($value, 1);
                                if(array_key_exists($k, $row)) {
                                    $tmp['set'][$field] = $row[$k];
                                }
                            }
                            else {
                                // constant
                            }
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

    private function processData($row)
    {
        $databox = $this->getByIdOrNameHelper->getDatabox($row['databoxId']);
        if(!$databox) {
            $this->logger->error(sprintf("unknown databox %s", $row['databoxId']));
            return $this;
        }
        $rec = $databox->get_record($row['record_id']);

        switch ($row['action']) {
            case 'UPDATE':
                $actions = [];      // actions as defined in https://app.swaggerhub.com/apis-docs/alchemy-fr/phraseanet.api.v3/1.0.0-oas3#/record/patchRecord

                // change collection ?
                if (array_key_exists('coll', $row)) {
                    $coll = collection::getByCollectionId($this->app, $databox, $row['coll']);
                    $actions['base_id'] = $coll->get_coll_id();
//                    $this->logger->info(sprintf("on databox(%s), record(%s) : move record to coll %s \n", $row['databoxId'], $row['record_id'], $coll->get_coll_id()));
                }

                // change sb ?
                if (array_key_exists('sb', $row)) {
                    $actions['status'] = [];
                    foreach (str_split(strrev($row['sb'])) as $bit => $val) {
                        if ($val == '0' || $val == '1') {
                            $actions['status'][] = ['bit' => $bit, 'state' => $val=='1'];
                        }
                    }
//                    $this->logger->info(sprintf("on databox(%s), record(%s) : set status to \"%s\" \n", $row['databoxId'], $row['record_id'], $status));
                }

                // set some field values ?
                foreach ($row['set'] as $field => $value) {
                    if(!array_key_exists('metadatas', $actions)) {
                        $actions['metadatas'] = [];
                    }
                    $actions['metadatas'][] = ['field_name' => $field, 'value' => $value];
//                    $this->logger->info(sprintf("on databox(%s), record(%s) : set field %s to \"%s\" \n", $row['databoxId'], $row['record_id'], $field, $value));
                }

                if(!empty($actions)) {
                    $js = json_encode($actions);
                    $this->logger->info(sprintf("on databox(%s), record(%s) :%s js=%s \n",
                        $row['databoxId'],
                        $row['record_id'],
                        $row['dry'] ? " [DRY]" : '',
                        $js
                    ));

                    if(!$row|'dry') {
                        $rec->setMetadatasByActions(json_decode($js, false));  // false: setMetadatasByActions expects object, not array !
                    }
                }
                break;

            case 'DELETE':
                if ($row['deletechildren'] && $rec->isStory()) {
                    /** @var record_adapter $child */
                    foreach ($rec->getChildren() as $child) {
                        $child->delete();
                        $this->logger->info(sprintf(
                            "on databox (%s) record (%s) :%s delete child record (%s) \n",
                            $row['databoxId'],
                            $rec->getRecordId(),
                            $row['dry'] ? " [DRY]" : '',
                            $child->getRecordId()
                        ));
                    }
                }
                $this->logger->info(sprintf(
                    "on databox (%s) record (%s) :%s delete record  \n",
                    $row['databoxId'],
                    $rec->getRecordId(),
                    $row['dry'] ? " [DRY]" : ''
                ));
                if(!$row['dry']) {
                    $rec->delete();
                }
                break;
        }

        return $this;
    }

    public function calcSQL(SimpleXMLElement $sxtask, bool $playTest = false): array
    {
        $ret = [
            'name'                 => $sxtask['name'] ? (string) $sxtask['name'] : 'sans nom',
            'name_htmlencoded'     => \p4string::MakeString(($sxtask['name'] ? $sxtask['name'] : 'sans nom'), 'html'),
            'active'               => trim($sxtask['active']) === '1',
            'dry'                  => trim($sxtask['dry']) === '1',
            'databoxId'            => null,
            'basename'             => '',
            'basename_htmlencoded' => '',
            'action'               => strtoupper($sxtask['action']),
            'sql'                  => null,
            'err'                  => '',
            'err_htmlencoded'      => '',
        ];

        try {
            $databox = $this->getByIdOrNameHelper->getDatabox($sxtask['databoxId']);
            if(!$databox) {
                throw new Exception(sprintf("unknown databox \"%s\"", $sxtask['databoxId']));
            }

            $sqlBuilder = new SqlBuilder($databox);

            $ret['databoxId'] = $databox->get_sbas_id();

            $ret['basename'] = $databox->get_label($this->app['locale']);
            $ret['basename_htmlencoded'] = htmlentities($ret['basename']);
            switch ($ret['action']) {
                case 'UPDATE':
                    $ret['sql'] = $this->calcUPDATE($sqlBuilder, $databox, $sxtask, $playTest);
                    break;
                case 'DELETE':
                    if($sxtask->then) {
                        throw new Exception("\"delete\" action cannot heve \"then\" clause");
                    }
                    $ret['sql'] = $this->calcUPDATE($sqlBuilder, $databox, $sxtask, $playTest);
                    $ret['deletechildren'] = (int)($sxtask['deletechildren']);
                    break;
                default:
                    throw new Exception(sprintf("bad action \"%s\"", $ret['action']));
            }
        }
        catch (Exception $e) {
            $ret['err'] = $e->getMessage();
            $ret['err_htmlencoded'] = htmlentities($e->getMessage());
            $this->logger->error($e->getMessage());
        }

        return $ret;
    }

    private function calcUPDATE(SqlBuilder $sqlBuilder, databox $databox, &$sxtask, $playTest)
    {
        $sqlBuilder->addFrom('record');
        $sqlBuilder->addSelect("record.record_id");

        // build the 'if' clause
        //
        $this->add_IF_Clauses($sqlBuilder, $databox, $sxtask->if);


        // build the "then" clauses to be negatively added to "where"
        //
        $this->add_THEN_Clauses($sqlBuilder, $databox, $sxtask->then);

        $sql_real = $sql_test = $sqlBuilder->getSql();
        $ret = array(
            'real' => array(
                'sql' => $sql_real,
                'sql_htmlencoded' => htmlentities($sql_real),
            ),
            'test' => array(
                'sql' => $sql_test,
                'sql_htmlencoded' => htmlentities($sql_test),
                'result' => null,
                'err' => ''
            )
        );

        if ($playTest) {
            $ret['test']['result'] = $this->playTest($databox, $sql_test);
        }
        return $ret;
    }

    private function playTest(databox $databox, $sql)
    {
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

    /**
     * compute the sql parts implementing the "from" clauses
     *
     * @param SqlBuilder $sqlBuilder
     * @param databox $databox
     * @param SimpleXMLElement $sxIf
     * @return void
     * @throws Exception
     */
    private function add_IF_Clauses(SqlBuilder $sqlBuilder, databox $databox, $sxIf)
    {
        if($sxIf->count() == 0) {
            return;
        }

        // criteria <record_type type="XXX" />
        foreach ($sxIf->record_type as $x) {
            $this->add_IF_RecordTypeClause($sqlBuilder, $databox, trim($x['type']));
        }

        // criteria <text field="XXX" compare="OP" value="ZZZ" />
        foreach ($sxIf->text as $x) {
            $this->add_IF_TextClause($sqlBuilder, $databox, trim($x['field']), trim($x['compare']), (string)$x['value']);
        }

        // criteria <number field="XXX" compare="OP" value="ZZZ" />
        foreach ($sxIf->number as $x) {
            $this->add_IF_NumberClause($sqlBuilder, $databox, trim($x['field']), trim($x['compare']), (double)($x['value']));
        }

        // criteria <is_set field="XXX" />
        foreach ($sxIf->is_set as $x) {
            $this->add_IF_FieldSetClause($sqlBuilder, $databox, trim($x['field']));
        }

        // criteria <is_unset field="XXX" />
        foreach ($sxIf->is_unset as $x) {
            $this->add_IF_FieldUnsetClause($sqlBuilder, $databox, trim($x['field']));
        }

        // criteria <date direction ="XXX" field="YYY" delta="Z period" computed="K" />
        foreach ($sxIf->date as $x) {
            $this->add_IF_DateClause($sqlBuilder, $databox, strtoupper(trim($x['direction'])), trim($x['field']), strtoupper(trim($x['delta'])));
        }

        // criteria <coll compare="OP" id="X,Y,Z" />
        foreach ($sxIf->coll as $x) {
            $this->add_IF_CollClause($sqlBuilder, $databox, trim($x['id']), trim($x['compare']));
        }

        // criteria <status mask="XXXXX" />
        foreach($sxIf->status as $x) {
            $this->add_IF_StatusClause($sqlBuilder, trim($x['mask']));
        }
    }

    /**
     * compute the sql parts implementing the "then" clauses
     *
     * @param SqlBuilder $sqlBuilder
     * @param databox $databox
     * @param SimpleXMLElement $sxThen
     * @return void
     * @throws Exception
     */
    private function add_THEN_Clauses(SqlBuilder $sqlBuilder, databox $databox, $sxThen)
    {
        if($sxThen->count() == 0) {
            return;
        }

        // action <compute_date direction="dir"...>
        foreach ($sxThen->compute_date as $x) {
            $this->add_THEN_ComputeDateClause($sqlBuilder, $databox, strtoupper(trim($x['direction'])), trim($x['field']), strtoupper(trim($x['delta'])), trim($x['computed']));
        }

        // action <coll id="X" />
        foreach ($sxThen->coll as $x) {
            $this->add_THEN_CollClause($sqlBuilder, $databox, trim($x['id']));
        }

        // action <status mask="XXXXX" />
        foreach($sxThen->status as $x) {
            $this->add_THEN_StatusClause($sqlBuilder, trim($x['mask']));
        }

        // action <set_field field="XXX" value="ZZZ" />
        foreach ($sxThen->set_field as $x) {
            $this->add_THEN_SetFieldClause($sqlBuilder, $databox, trim($x['field']), (string)$x['value']);
        }
    }

    private function add_IF_RecordTypeClause(SqlBuilder $sqlBuilder, databox $databox, string $type)
    {
        switch (strtoupper($type)) {
            case 'RECORD':
                $sqlBuilder->addWhere('parent_record_id!=record_id');
                break;
            case 'STORY':
                $sqlBuilder->addWhere('parent_record_id=record_id');
                break;
            default:
                throw new Exception(sprintf("bad record_type (%s)\n", $type));
        }
    }

    private function add_IF_NumberClause(SqlBuilder $sqlBuilder, databox $databox, string $fieldName, string $operator, float $value)
    {
        if (!in_array($operator, array('<', '>', '<=', '>=', '=', '!='))) {
            throw new Exception(sprintf("bad comparison operator (%s)\n", $operator));
        }
        switch ($fieldName) {
            case "#filesize":
                $ijoin = $sqlBuilder->incIjoin();
                $sqlBuilder->addFrom(sprintf('INNER JOIN subdef AS p%d ON(p%d.record_id=record.record_id)', $ijoin, $ijoin));
                $sqlBuilder->addWhere(sprintf(
                    'p%d.name=%s',
                    $ijoin,
                    $databox->get_connection()->quote('document')
                ));
                $sqlBuilder->addWhere(sprintf(
                    'p%d.size%s%s',
                    $ijoin,
                    $operator,
                    $value
                ));
                break;
            default:
                $field = $this->getByIdOrNameHelper->getField($databox, $fieldName);
                if (!$field) {
                    throw new Exception(sprintf("unknown field (%s)\n", $fieldName));
                }
                $ijoin = $sqlBuilder->incIjoin();
                $sqlBuilder->addFrom(sprintf('INNER JOIN metadatas AS p%d ON(p%d.record_id=record.record_id)', $ijoin, $ijoin));
                $sqlBuilder->addWhere(sprintf(
                    'p%d.meta_struct_id=%s',
                    $ijoin,
                    $databox->get_connection()->quote($field->get_id())
                ));
                $sqlBuilder->addWhere(sprintf(
                    'CAST(p%d.value AS DECIMAL)%s%s',
                    $ijoin,
                    $operator,
                    $value
                ));
                break;
        }
    }

    private function add_IF_FieldSetClause(SqlBuilder $sqlBuilder, databox $databox, string $fieldName)
    {
        $field = $this->getByIdOrNameHelper->getField($databox, $fieldName);
        if (!$field) {
            throw new Exception(sprintf("unknown field (%s)\n", $fieldName));
        }
        $ijoin = $sqlBuilder->incIjoin();
        $sqlBuilder->addFrom(sprintf(
            "INNER JOIN metadatas AS p%d ON(p%d.record_id=record.record_id)",
            $ijoin,
            $ijoin
        ));
        $sqlBuilder->addWhere(sprintf(
            "p%d.meta_struct_id=%s",
            $ijoin,
            $databox->get_connection()->quote($field->get_id())
        ));
    }

    private function add_IF_FieldUnsetClause(SqlBuilder $sqlBuilder, databox $databox, string $fieldName)
    {
        $field = $this->getByIdOrNameHelper->getField($databox, $fieldName);
        if (!$field) {
            throw new Exception(sprintf("unknown field (%s)\n", $fieldName));
        }
        $ijoin = $sqlBuilder->incIjoin();
        $sqlBuilder->addFrom(sprintf(
            'LEFT JOIN metadatas AS p%d ON(record.record_id=p%d.record_id AND p%d.meta_struct_id=%s)',
            $ijoin,
            $ijoin,
            $ijoin,
            $databox->get_connection()->quote($field->get_id())
        ));
        $sqlBuilder->addWhere(sprintf(
            "ISNULL(p%d.id)",
            $ijoin
        ));
    }

    private function add_IF_TextClause(SqlBuilder $sqlBuilder, databox $databox, string $fieldName, string $operator, string $value)
    {
        $field = $this->getByIdOrNameHelper->getField($databox, $fieldName);
        if (!$field) {
            throw new Exception(sprintf("unknown field (%s)\n", $fieldName));
        }
        if (!in_array($operator, array('<', '>', '<=', '>=', '=', '!='))) {
            throw new Exception(sprintf("bad comparison operator (%s)\n", $operator));
        }
        $ijoin = $sqlBuilder->incIjoin();
        $sqlBuilder->addFrom(sprintf("INNER JOIN metadatas AS p%d ON(p%d.record_id=record.record_id)", $ijoin, $ijoin));
        $sqlBuilder->addWhere(sprintf(
            "p%d.meta_struct_id=%s ",
            $ijoin,
            $databox->get_connection()->quote($field->get_id())
        ));
        $sqlBuilder->addWhere(sprintf(
            "p%d.value%s%s",
            $ijoin,
            $operator,
            $databox->get_connection()->quote($value)
        ));
    }

    private function add_IF_DateClause(SqlBuilder $sqlBuilder, databox $databox, string $dir, string $fieldName, string $delta)
    {
        $unit = "DAY";
        $matches = [];
        $computedSql = null;
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
                    throw new Exception(sprintf("bad delta (%s)\n", $delta));
                }
            }
            else {
                throw new Exception(sprintf("bad delta (%s)\n", $delta));
            }
        }

        $dirop = "";
        if (in_array($dir, array('BEFORE', 'AFTER'))) {
            $dirop .= ($dir == 'BEFORE') ? '<' : '>=';
        }
        else {
            // bad direction
            throw new Exception(sprintf("bad direction (%s)\n", $dir));
        }

        switch ($fieldName) {
            case '#moddate':
            case '#credate':
                $dbField = substr($fieldName, 1);
                if($delta == 0) {
                    $computedSql = sprintf("record.%s AS DATETIME", $dbField);
                }
                else {
                    $computedSql = sprintf("(record.%s%sINTERVAL %d %s)", $dbField, $delta > 0 ? '+' : '-', abs($delta), $unit);
                }
                $sqlBuilder->addWhere(sprintf(
                    "NOW()%s%s",
                    $dirop,
                    $computedSql
                ));
                break;

            default:
                $field = $this->getByIdOrNameHelper->getField($databox, $fieldName);
                if (!$field) {
                    throw new Exception(sprintf("unknown field (%s)\n", $fieldName));
                }

                $ijoin = $sqlBuilder->incIjoin();

                // prevent malformed dates to act
                $sqlBuilder->addWhere(sprintf(
                    "!ISNULL(CAST(p%d.value AS DATETIME))",
                    $ijoin
                ));

                if($delta == 0) {
                    $computedSql = sprintf("CAST(p%d.value AS DATETIME)", $ijoin);
                }
                else {
                    $computedSql = sprintf("(p%d.value%sINTERVAL %d %s)", $ijoin, $delta > 0 ? '+' : '-', abs($delta), $unit);
                }

                $sqlBuilder->addFrom(sprintf(
                    'INNER JOIN metadatas AS p%d ON(p%d.record_id=record.record_id)',
                    $ijoin,
                    $ijoin
                ));
                $sqlBuilder->addWhere(sprintf(
                    "p%d.meta_struct_id=%s",
                    $ijoin,
                    $databox->get_connection()->quote($field->get_id())
                ));
                $sqlBuilder->addWhere(sprintf(
                    "NOW()%s%s",
                    $dirop,
                    $computedSql
                ));

                break;
        }
    }

    private function add_IF_StatusClause(SqlBuilder $sqlBuilder, string $mask)
    {
        $mask = preg_replace('/[^0-1]/', 'x', $mask);
        $mx = str_replace(' ', '0', ltrim(str_replace(['0', 'x'], [' ', ' '], $mask)));
        $ma = str_replace(' ', '0', ltrim(str_replace(['x', '0'], [' ', '1'], $mask)));

        if ($mx && $ma) {
            $sqlBuilder->addWhere(sprintf("((status ^ 0b%s) & 0b%s)=0", $mx, $ma));
        }
        elseif ($mx) {
            $sqlBuilder->addWhere(sprintf("(status ^ 0b%s)=0", $mx));
        }
        elseif ($ma) {
            $sqlBuilder->addWhere(sprintf("(status & 0b%s)=0", $ma));
        }
    }

    /**
     * add coll clause to the query builder
     *
     * @param SqlBuilder $sqlBuilder
     * @param databox $databox
     * @param string $collList
     * @param string $operator
     * @return void
     * @throws Exception
     */
    private function add_IF_CollClause(SqlBuilder $sqlBuilder, databox $databox, string $collList, string $operator)
    {
        if(!in_array($operator, ['=', '!='])) {
            // bad operator
            throw new Exception(sprintf("bad comparison operator (%s)\n", $operator));
        }
        $tcoll = explode(',', $collList);
        foreach ($tcoll as $i => $c) {
            $coll = $this->getByIdOrNameHelper->getCollection($databox->get_sbas_id(), $c);
            if(!$coll) {
                throw new Exception(sprintf("unknown collection %s", $c));
            }
            $tcoll[$i] = $coll->get_coll_id();
        }
        if(count($tcoll) > 0) {
            if ($operator == '=') {
                if (count($tcoll) == 1) {
                    $sqlBuilder->addWhere('coll_id=' . $tcoll[0]);
                }
                else {
                    $sqlBuilder->addWhere('coll_id IN(' . implode(',', $tcoll) . ')');
                }
            }
            else {
                if (count($tcoll) == 1) {
                    $sqlBuilder->addWhere('coll_id!=' . $tcoll[0]);
                }
                else {
                    $sqlBuilder->addWhere('coll_id NOT IN(' . implode(',', $tcoll) . ')');
                }
            }
        }
    }

    private function checkComputedRefKey(string $s)
    {
        if($s === '') {
            throw new Exception(sprintf("mssing compute reference\n"));
        }
        $_s = strtolower($s);
        foreach(str_split($_s) as $i => $c) {
            if(!($c=='_' || ($c >= 'a' && $c <= 'z') || ($i>0 && $c >= '0' && $c <= '9'))) {
                throw new Exception(sprintf("bad compute reference (%s)\n", $s));
            }
        }
    }

    private function add_THEN_ComputeDateClause(SqlBuilder $sqlBuilder, databox $databox, string $dir, string $fieldName, string $delta, string $computedRefKey)
    {
        $this->checkComputedRefKey($computedRefKey);

        $unit = "DAY";
        $matches = [];
        $computedSql = null;
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
                    throw new Exception(sprintf("bad delta (%s)\n", $delta));
                }
            }
            else {
                throw new Exception(sprintf("bad delta (%s)\n", $delta));
            }
        }

        $dirop = "";
        if (in_array($dir, array('BEFORE', 'AFTER'))) {
            $dirop .= ($dir == 'BEFORE') ? '<' : '>=';
        }
        else {
            // bad direction
            throw new Exception(sprintf("bad direction (%s)\n", $dir));
        }

        switch ($fieldName) {
            case '#moddate':
            case '#credate':
                $dbField = substr($fieldName, 1);
                if($delta == 0) {
                    $computedSql = sprintf("record.%s AS DATETIME", $dbField);
                }
                else {
                    $computedSql = sprintf("(record.%s%sINTERVAL %d %s)", $dbField, $delta > 0 ? '+' : '-', abs($delta), $unit);
                }
                break;

            default:
                $field = $this->getByIdOrNameHelper->getField($databox, $fieldName);
                if (!$field) {
                    throw new Exception(sprintf("unknown field (%s)\n", $fieldName));
                }

                $ijoin = $sqlBuilder->incIjoin();

                // prevent malformed dates to act
                $sqlBuilder->addWhere(sprintf(
                    "!ISNULL(CAST(p%d.value AS DATETIME))",
                    $ijoin
                ));

                if($delta == 0) {
                    $computedSql = sprintf("CAST(p%d.value AS DATETIME)", $ijoin);
                }
                else {
                    $computedSql = sprintf("(p%d.value%sINTERVAL %d %s)", $ijoin, $delta > 0 ? '+' : '-', abs($delta), $unit);
                }

                $sqlBuilder->addFrom(sprintf(
                    'INNER JOIN metadatas AS p%d ON(p%d.record_id=record.record_id)',
                    $ijoin,
                    $ijoin
                ));

                break;
        }

        if($computedRefKey && $computedSql !== null) {
            $sqlBuilder->addSelect(sprintf("%s AS %s",
                $computedSql,
                $databox->get_connection()->quoteIdentifier($computedRefKey)
            ));
            $sqlBuilder->addReference($computedRefKey, $computedSql);
        }
    }

    /**
     * add THEN.coll clause to the query builder (negated)
     *
     * @param SqlBuilder $sqlBuilder
     * @param databox $databox
     * @param string $collId
     * @return void
     * @throws Exception
     */
    private function add_THEN_CollClause(SqlBuilder $sqlBuilder, databox $databox, string $collId)
    {
        $coll = $this->getByIdOrNameHelper->getCollection($databox->get_sbas_id(), $collId);
        if(!$coll) {
            throw new Exception(sprintf("unknown collection %s", $collId));
        }
        $sqlBuilder->addNegWhere('coll_id=' . $coll->get_coll_id());
    }

    private function add_THEN_StatusClause(SqlBuilder $sqlBuilder, string $mask)
    {
        $mask = preg_replace('/[^0-1]/', 'x', $mask);
        $mx = str_replace(' ', '0', ltrim(str_replace(['0', 'x'], [' ', ' '], $mask)));
        $ma = str_replace(' ', '0', ltrim(str_replace(['x', '0'], [' ', '1'], $mask)));

        if ($mx && $ma) {
            $sqlBuilder->addNegWhere(sprintf("((status ^ 0b%s) & 0b%s)=0", $mx, $ma));
        }
        elseif ($mx) {
            $sqlBuilder->addNegWhere(sprintf("(status ^ 0b%s)=0", $mx));
        }
        elseif ($ma) {
            $sqlBuilder->addNegWhere(sprintf("(status & 0b%s)=0", $ma));
        }
    }

    private function add_THEN_SetFieldClause(SqlBuilder $sqlBuilder, databox $databox, string $fieldName, string $value)
    {
        $field = $this->getByIdOrNameHelper->getField($databox, $fieldName);
        if (!$field) {
            throw new Exception(sprintf("unknown field (%s)\n", $fieldName));
        }

        if(substr($value, 0, 1) === '$') {
            // reference to a previously computed expression (only THEN.compute_date does that)
            $k = substr($value, 1);
            $this->checkComputedRefKey($k);

            if(!($value = $sqlBuilder->getReference($k))) {
                throw new Exception(sprintf("unknown reference (\$%s)\n", $k));
            }
        }
        else {
            // constant
            $value = $databox->get_connection()->quote($value);
        }

        $ijoin = $sqlBuilder->incIjoin();
        $sqlBuilder->addFrom(sprintf(
            "LEFT JOIN metadatas AS p%d ON(p%d.record_id=record.record_id AND p%d.meta_struct_id=%s AND p%d.value=%s)",
            $ijoin,
            $ijoin,
            $ijoin,
            $databox->get_connection()->quote($field->get_id()),
            $ijoin,
            $value
        ));
        $sqlBuilder->addWhere(sprintf(
            "ISNULL(p%d.id)",
            $ijoin
        ));
    }

}
