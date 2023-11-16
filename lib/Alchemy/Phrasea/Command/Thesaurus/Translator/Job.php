<?php

namespace Alchemy\Phrasea\Command\Thesaurus\Translator;

use collection;
use databox;
use DOMNodeList;
use DOMXpath;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;
use thesaurus_xpath;
use Unicode;

class Job
{
    const CONDENSED_REPORT_NOT_TRANSLATED = 'notTranslated';
    const CONDENSED_REPORT_INCOMPLETELY_TRANSLATED = 'incompletelyTranslated';
    const CONDENSED_REPORT_FULLY_TRANSLATED = 'fullyTranslated';


    private $active = true;

    /** @var array[]  */
    private $condensedReportCounts = [
        self::CONDENSED_REPORT_NOT_TRANSLATED => [],
        self::CONDENSED_REPORT_INCOMPLETELY_TRANSLATED => [],
        self::CONDENSED_REPORT_FULLY_TRANSLATED => []
    ];

    /** @var string[] */
    private $errors = [];       // error messages while parsing conf

    /** @var databox|null $databox */
    private $databox = null;

    /** @var array */
    private $selectRecordParams = [];

    private $selectRecordsSql = null;

    /** @var OutputInterface */
    private $output;

    /** @var DOMXpath|false|thesaurus_xpath */
    private $xpathTh;

    /** @var int flush every n records */
    private $bulk = 10;

    /** @var GlobalConfiguration */
    private $globalConfiguration;

    /** @var collection|null */
    private $setCollection = null;
    /** @var string */
    private $setStatus = null;  // format 0xx1100xx01xxxx

    /** @var Action[] */
    private $actions;

    /** @var array  */
    private $selectRecordFieldIds = [];     // ids of fields required by actions

    /** @var int */
    private $recordsDone;      // for condensed report


    /**
     * @param GlobalConfiguration $globalConfiguration
     * @param string $job_name
     * @param array $job_conf
     * @param Unicode $unicode
     * @param OutputInterface $output
     */
    public function __construct(GlobalConfiguration $globalConfiguration, string $job_name, array $job_conf, Unicode $unicode, OutputInterface $output)
    {
        $this->globalConfiguration = $globalConfiguration;
        $this->output = $output;

        $this->actions = [];
        $this->errors = [];

        if (array_key_exists('active', $job_conf) && $job_conf['active'] === false) {
            $this->active = false;
            return;
        }

        foreach (['active', 'databox', 'actions'] as $mandatory) {
            if (!isset($job_conf[$mandatory])) {
                $this->errors[] = sprintf("Missing mandatory setting (%s).", $mandatory);
            }
        }
        if (!($this->databox = $globalConfiguration->getDatabox($job_conf['databox']))) {
            $this->errors[] = sprintf("unknown databox (%s).", $job_conf['databox']);
        }
        $ifCollection = null;
        if(array_key_exists('if_collection', $job_conf)) {
            if(!($ifCollection = $globalConfiguration->getCollection($this->databox->get_sbas_id(), $job_conf['if_collection']))) {
                $this->errors[] = sprintf("unknown setCollection (%s).", $job_conf['if_collection']);
            }
        }
        if(array_key_exists('set_collection', $job_conf)) {
            if(!($this->setCollection = $globalConfiguration->getCollection($this->databox->get_sbas_id(), $job_conf['set_collection']))) {
                $this->errors[] = sprintf("unknown setCollection (%s).", $job_conf['set_collection']);
            }
        }
        if(array_key_exists('set_status', $job_conf)) {
            $this->setStatus = $job_conf['set_status'];
        }
        if(array_key_exists('bulk', $job_conf)) {
            if( ($this->bulk = (int) $job_conf['bulk']) < 1) {
                $this->errors[] = sprintf("bulk should be >= 1.");
            }
        }

        $this->xpathTh = $this->databox->get_xpath_thesaurus();



        // load actions
        //
        $this->selectRecordFieldIds = [];
        foreach($job_conf['actions'] as $action_name => $action_conf) {
            $action = new Action($this, $action_conf, $unicode, $this->output);
            if($action->isActive()) {
                $this->selectRecordFieldIds = array_merge($this->selectRecordFieldIds, $action->getSelectRecordFieldIds());
                $this->errors = array_merge($this->errors, $action->getErrors());
                $this->actions[$action_name] = $action;
            }
            else {
                unset($action);
                $output->writeln(sprintf("action \"%s\" of job \"%s\" is inactive: ignored.", $action_name, $job_name));
            }
        }
        $this->selectRecordFieldIds = array_unique($this->selectRecordFieldIds);

        if (!empty($this->errors)) {
            return;
        }


        // build records select sql
        //
        $selectRecordsClauses = [
            '`record_id` > :minrid'
        ];
        $this->selectRecordParams = [
            ':minrid' => 0
        ];
        if ($ifCollection) {
            $selectRecordsClauses[] = "`coll_id` = " . (int)($ifCollection->get_coll_id());
        }

        if (array_key_exists('if_status', $job_conf)) {
            $_and = '0b'.str_replace(['0', 'x'], ['1', '0'], $job_conf['if_status']);
            $_equ = '0b'.str_replace('x', '0', $job_conf['if_status']);
            $selectRecordsClauses[] = "`status` & " . $_and . " = " . $_equ;
        }

        $cnx = $this->databox->get_connection();
        $selectFieldsClause = "`meta_struct_id` IN ("
            . join(
                ',',
                array_map(function ($id) use ($cnx) {
                    return $cnx->quote($id);
                }, $this->selectRecordFieldIds)
            )
            . ")";

        $sql = "SELECT `r1`.`record_id`, `meta_struct_id`, `metadatas`.`id` AS meta_id, `value` FROM\n";
        $sql .= " (SELECT `record_id` FROM `record` WHERE ".join(" AND ", $selectRecordsClauses)." LIMIT ".$this->bulk.") AS `r1`\n";
        $sql .= " LEFT JOIN `metadatas` ON(`metadatas`.`record_id`=`r1`.`record_id`\n";
        $sql .= "   AND " . $selectFieldsClause . ")\n";
        $sql .= " ORDER BY `record_id` ASC";
        $this->selectRecordsSql = $sql;
    }

    public function run()
    {
        $this->recordsDone = 0;

        $stmt = $this->databox->get_connection()->prepare($this->selectRecordsSql);


//        $metas = $emptyValues = array_map(function () {
//            return [];
//        }, array_flip($this->selectRecordFieldIds));

        $minrid = 0;
        do {
            $nrows = 0;
            $currentRid = '?';
            $metas = [];

            $this->selectRecordParams[':minrid'] = $minrid;
            $stmt->execute($this->selectRecordParams);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $nrows++;
                if ($currentRid == '?') {
                    $currentRid = $row['record_id'];
                }
                if ($row['record_id'] !== $currentRid) {
                    // change record
                    $this->doRecord($currentRid, $metas);  // flush previous record
                    $currentRid = $row['record_id'];
                    // $metas = $emptyValues;
                    $metas = [];
                }
                if ($row['meta_struct_id'] !== null) {   // left join : a record may not have any required field
                    if (!array_key_exists($row['meta_struct_id'], $metas)) {
                        $metas[$row['meta_struct_id']] = [];
                    }
                    $metas[$row['meta_struct_id']][$row['meta_id']] = $row['value'];
                }
            }
            if ($currentRid !== '?') {
                $this->doRecord($currentRid, $metas);  // flush last record
            }

            $stmt->closeCursor();
            $minrid = $currentRid;
        }
        while($nrows > 0);

        // condensed report
        //
        if($this->globalConfiguration->getReportFormat() === 'condensed') {
            $this->output->writeln(sprintf("%d records done.", $this->recordsDone));
            if(!empty($this->condensedReportCounts[self::CONDENSED_REPORT_NOT_TRANSLATED])) {
                ksort($this->condensedReportCounts[self::CONDENSED_REPORT_NOT_TRANSLATED], SORT_STRING|SORT_FLAG_CASE);
                $this->output->writeln("Not translated terms:");
                foreach ($this->condensedReportCounts[self::CONDENSED_REPORT_NOT_TRANSLATED] as $term => $n) {
                    $this->output->writeln(sprintf(" - \"%s\" (%d times)", $term, $n));
                }
            }
            if(!empty($this->condensedReportCounts[self::CONDENSED_REPORT_INCOMPLETELY_TRANSLATED])) {
                ksort($this->condensedReportCounts[self::CONDENSED_REPORT_INCOMPLETELY_TRANSLATED], SORT_STRING|SORT_FLAG_CASE);
                $this->output->writeln("Incompletely translated terms:");
                foreach ($this->condensedReportCounts[self::CONDENSED_REPORT_INCOMPLETELY_TRANSLATED] as $term => $n) {
                    $this->output->writeln(sprintf(" - \"%s\" (%d times)", $term, $n));
                }
            }
            if(!empty($this->condensedReportCounts[self::CONDENSED_REPORT_FULLY_TRANSLATED])) {
                ksort($this->condensedReportCounts[self::CONDENSED_REPORT_FULLY_TRANSLATED], SORT_STRING|SORT_FLAG_CASE);
                $this->output->writeln("Fully translated terms:");
                foreach ($this->condensedReportCounts[self::CONDENSED_REPORT_FULLY_TRANSLATED] as $term => $n) {
                    $this->output->writeln(sprintf(" - \"%s\" (%d times)", $term, $n));
                }
            }
        }
    }

    private function doRecord(string $record_id, array $metas)
    {
        $reportFormat = $this->globalConfiguration->getReportFormat();
        if($reportFormat !== 'condensed') {
            $this->output->writeln(sprintf("\trecord id: %s", $record_id));
        }

        $meta_to_delete = [];       // key = id, to easily keep unique
        $meta_to_add = [];

        // play all actions
        //
        foreach($this->actions as $action_name => $action) {
            if($reportFormat !== 'condensed') {
                $this->output->writeln(sprintf("\t\tplaying action \"%s\"", $action_name));
            }
            $action->doAction($metas, $meta_to_delete, $meta_to_add);
        }

        unset($metas);

        $actions = [];

        $metadatas = [];
        foreach ($meta_to_delete as $id => $value) {
            $metadatas[] = [
                'action' => "delete",
                'meta_id' => $id,
                '_value_' => $value
            ];
        }
        foreach($meta_to_add as $struct_id => $values) {
            $metadatas[] = [
                'action' => "add",
                'meta_struct_id' => $struct_id,
                'value' => $values
            ];
        }
        if(!empty($metadatas)) {
            $actions['metadatas'] = $metadatas;
        }
        unset($metadatas);

        if(!is_null($this->setCollection)) {
            $actions['base_id'] = $this->setCollection->get_base_id();
        }

        if(!is_null($this->setStatus)) {
            $status = [];
            foreach(str_split(strrev($this->setStatus), 1) as $bit => $v) {
                if($v === '0' || $v === '1') {
                    $status[] = [
                        'bit' => $bit,
                        'state' => $v === '1'
                    ];
                }
            }
            if(!empty($status)) {
                $actions['status'] = $status;
            }
        }

        if(count($actions) > 0) {
            $jsActions = json_encode($actions, JSON_PRETTY_PRINT);
            if($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                $this->output->writeln(sprintf("<info>JS : %s</info>", $jsActions));
            }
    
            if (!$this->globalConfiguration->isDryRun()) {
                $record = $this->getDatabox()->getRecordRepository()->find($record_id);
                $record->setMetadatasByActions(json_decode($jsActions));
            }
        }
        $this->recordsDone++;
    }

    public function addToCondensedReport(string $term, string $where)
    {
        if($this->globalConfiguration->getReportFormat() !== 'condensed') {
            return;
        }
        if(!array_key_exists($where, $this->condensedReportCounts)) {
            $this->condensedReportCounts[$where] = [];
        }
        if(!array_key_exists($term, $this->condensedReportCounts[$where])) {
            $this->condensedReportCounts[$where][$term] = 0;
        }
        $this->condensedReportCounts[$where][$term]++;
    }


    /**
     * @return GlobalConfiguration
     */
    public function getGlobalConfiguration(): GlobalConfiguration
    {
        return $this->globalConfiguration;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * @return databox|null
     */
    public function getDatabox()
    {
        return $this->databox;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    public function getDataboxField(string $fieldIdOrName)
    {
        return $this->globalConfiguration->getField($this->databox->get_sbas_id(), $fieldIdOrName);
    }

    /**
     * @return DOMXpath|false|thesaurus_xpath
     */
    public function getXpathTh()
    {
        return $this->xpathTh;
    }
}
