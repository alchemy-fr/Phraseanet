<?php

namespace Alchemy\Phrasea\Command\Thesaurus\Translator;

use databox;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXpath;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;
use thesaurus_xpath;
use Unicode;

class Job
{
    const NEVER_CLEANUP_SOURCE = 'never';
    const ALWAYS_CLEANUP_SOURCE = 'always';
    const CLEANUP_SOURCE_IF_TRANSLATED = 'if_translated';


    private $active = true;

    /** @var string[] */
    private $errors = [];       // error messages while parsing conf

    /** @var databox|null $databox */
    private $databox = null;

    /** @var array */
    private $selectRecordParams = [];

    private $selectRecordsSql = null;

    /** @var array  list of field ids of "source_field" (unique) and "destination_fields" (many) */
    private $selectRecordFieldIds;

    /**
     * @var OutputInterface
     */
    private $output;

    private $source_field;    // infos about the "source_field"
    private $destination_fields;     // infos about the "destination_fields" (key=lng)

    /**
     * @var Unicode
     */
    private $unicode;

    /** @var DOMXpath|false|thesaurus_xpath */
    private $xpathTh;

    /**
     * @var DOMNodeList
     * The thesaurus branch(es) linked to the "source_field"
     */
    private $tbranches;

    /** @var bool */
    private $cleanupDestination;

    /** @var string */
    private $cleanupSource = self::NEVER_CLEANUP_SOURCE;
    /**
     * @var GlobalConfiguration
     */
    private $globalConfiguration;
    /**
     * @var array
     */
    private $job_conf;
    /**
     * @var \collection|null
     */
    private $setCollection = null;
    /**
     * @var string
     */
    private $setStatus = null;  // format 0xx1100xx01xxxx

    /**
     * @param GlobalConfiguration $globalConfiguration
     * @param array $job_conf
     */
    public function __construct($globalConfiguration, $job_conf, Unicode $unicode, OutputInterface $output)
    {
        $this->globalConfiguration = $globalConfiguration;
        $this->job_conf = $job_conf;
        $this->unicode = $unicode;
        $this->output = $output;

        if (array_key_exists('active', $job_conf) && $job_conf['active'] === false) {
            $this->active = false;

            return;
        }

        $this->errors = [];
        foreach (['active', 'databox', 'source_field', 'destination_fields'] as $mandatory) {
            if (!isset($job_conf[$mandatory])) {
                $this->errors[] = sprintf("Missing mandatory setting (%s).", $mandatory);
            }
        }
        if (!empty($this->errors)) {
            return;
        }

        if (!($this->databox = $globalConfiguration->getDatabox($job_conf['databox']))) {
            $this->errors[] = sprintf("unknown databox (%s).", $job_conf['databox']);

            return;
        }

        if(array_key_exists('set_collection', $job_conf)) {
            if(!($this->setCollection = $globalConfiguration->getCollection($this->databox->get_sbas_id(), $job_conf['set_collection']))) {
                $this->errors[] = sprintf("unknown setCollection (%s).", $job_conf['set_collection']);

                return;
            }
        }

        if(array_key_exists('set_status', $job_conf)) {
            $this->setStatus = $job_conf['set_status'];
        }


        $cnx = $this->databox->get_connection();

        // get infos about the "source_field"
        //
        $sql = "SELECT `id`, `tbranch` FROM `metadatas_structure` WHERE `name` = :name AND `tbranch` != ''";
        $stmt = $cnx->executeQuery($sql, [':name' => $job_conf['source_field']]);
        $this->source_field = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$this->source_field) {
            $this->errors[] = sprintf("field (%s) not found or not linked to thesaurus.", $job_conf['source_field']);

            return;
        }
        $this->source_field['lng'] = array_key_exists('source_lng', $job_conf) ? $job_conf['source_lng'] : null;
        $this->selectRecordFieldIds[] = $this->source_field['id'];
        $this->xpathTh = $this->databox->get_xpath_thesaurus();
        $this->tbranches = $this->xpathTh->query($this->source_field['tbranch']);
        if (!$this->tbranches || $this->tbranches->length <= 0) {
            $this->errors[] = sprintf("thesaurus branch(es) (%s) not found.", $this->source_field['tbranch']);

            return;
        }

        // get infos about the "destination_fields"
        //
        $this->destination_fields = [];
        $sql = "SELECT `id`, `name` FROM `metadatas_structure` WHERE `name` = :name ";
        $stmt = $cnx->prepare($sql);
        foreach ($job_conf['destination_fields'] as $tf) {
            list($lng, $fname) = explode(':', $tf);
            $stmt->execute([':name' => $fname]);
            if (!($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
                $this->output->writeln(sprintf("<warning>undefined field (%s)  (ignored).</warning>", $fname));
                continue;
            }
            $this->destination_fields[$lng] = $row;
            $stmt->closeCursor();

            $this->selectRecordFieldIds[] = $row['id'];
        }

        if (empty($this->destination_fields)) {
            $this->errors[] = sprintf("<warning>no \"destination_field\" found.</warning>");

            return;
        }

        // misc settings
        $this->cleanupDestination = array_key_exists('cleanup_destination', $job_conf) && $job_conf['cleanup_destination'] === true;
        $this->cleanupSource = array_key_exists('cleanup_source', $job_conf) ? $job_conf['cleanup_source'] : self::NEVER_CLEANUP_SOURCE;

        // build records select sql
        //
        $selectRecordClauses = [];
        $this->selectRecordParams = [];
        if (array_key_exists('if_collection', $job_conf)) {
            if (!($coll = $globalConfiguration->getCollection($job_conf['databox'], $job_conf['if_collection']))) {
                $this->errors[] = sprintf("unknown collection (%s)", $job_conf['if_collection']);

                return;
            }
            $selectRecordClauses[] = "`coll_id` = :coll_id";
            $this->selectRecordParams[':coll_id'] = $coll->get_coll_id();
        }

        if (array_key_exists('if_status', $job_conf)) {
            $selectRecordClauses[] = "`status` & b:sb_and = b:sb_equ";
            $this->selectRecordParams[':sb_and'] = str_replace(['0', 'x'], ['1', '0'], $job_conf['if_status']);
            $this->selectRecordParams[':sb_equ'] = str_replace('x', '0', $job_conf['if_status']);
        }

        $selectRecordClauses[] = "`meta_struct_id` IN ("
            . join(
                ',',
                array_map(function ($id) use ($cnx) {
                    return $cnx->quote($id);
                }, $this->selectRecordFieldIds)
            )
            . ")";

        $sql = "SELECT `record_id`, `meta_struct_id`, `metadatas`.`id` AS meta_id, `value` FROM";
        $sql .= " `record` INNER JOIN `metadatas` USING(`record_id`)";
        $sql .= " WHERE " . join(" AND ", $selectRecordClauses);
        $sql .= " ORDER BY `record_id` ASC";
        $this->selectRecordsSql = $sql;
    }

    public function run()
    {
        $cnx = $this->databox->get_connection();
        $stmt = $cnx->executeQuery($this->selectRecordsSql, $this->selectRecordParams);

        $currentRid = '?';
        $metas = $emptyValues = array_map(function () {
            return [];
        }, array_flip($this->selectRecordFieldIds));
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($currentRid == '?') {
                $currentRid = $row['record_id'];
            }
            if ($row['record_id'] !== $currentRid) {
                // change record
                $this->doRecord($currentRid, $metas);  // flush previous record
                $currentRid = $row['record_id'];
                $metas = $emptyValues;
            }

            $metas[$row['meta_struct_id']][$row['meta_id']] = $row['value'];
        }
        if($currentRid !== '?') {
            $this->doRecord($currentRid, $metas);  // flush last record
        }

        $stmt->closeCursor();
    }

    private function doRecord($record_id, $metas)
    {
        $this->output->writeln(sprintf("record id: %s", $record_id));

        $source_field_id = $this->source_field['id'];
        $meta_to_delete = [];       // key = id, to easily keep unique
        $meta_to_add = [];

        if ($this->cleanupDestination) {
            foreach ($this->destination_fields as $lng => $destination_field) {
                $destination_field_id = $destination_field['id'];
                foreach ($metas[$destination_field_id] as $meta_id => $value) {
                    $meta_to_delete[$meta_id] = $value;
                }
                unset($meta_id, $value);
            }
            unset($lng, $destination_field, $destination_field_id);
        }

        // loop on every value of the "source_field"
        //
        foreach ($metas[$source_field_id] as $source_meta_id => $source_value) {

            $t = $this->splitTermAndContext($source_value);
            $q = '@w=\'' . \thesaurus::xquery_escape($this->unicode->remove_indexer_chars($t[0])) . '\'';
            if ($t[1]) {
                $q .= ' and @k=\'' . \thesaurus::xquery_escape($this->unicode->remove_indexer_chars($t[1])) . '\'';
            }
            if(!is_null($this->source_field['lng'])) {
                $q .= ' and @lng=\'' . \thesaurus::xquery_escape($this->source_field['lng']) . '\'';
            }
            $q = '//sy[' . $q . ']/../sy';
            unset($t);

            // loop on every tbranch (one field may be linked to many branches)
            //
            $translations = [];             // ONE translation per lng (first found in th)
            /** @var DOMNode $tbranch */
            foreach ($this->tbranches as $tbranch) {
                if (!($nodes = $this->xpathTh->query($q, $tbranch))) {
                    $this->output->writeln(sprintf(" - \"%s\"  <warning>xpath error on (%s), ignored.</warning>", $source_value, $q));
                    continue;
                }

                // loop on every synonym
                //
                /** @var DOMElement $node */
                foreach ($nodes as $node) {
                    $lng = $node->getAttribute('lng');

                    // ignore synonyms not in one of the "destination_field" languages
                    //
                    if (!array_key_exists($lng, $this->destination_fields)) {
                        continue;
                    }

                    $translated_value = $node->getAttribute('v');

                    $destination_field_id = $this->destination_fields[$lng]['id'];
                    if (!array_key_exists($lng, $translations)) {
                        if (($destination_meta_id = array_search($translated_value, $metas[$destination_field_id])) === false) {
                            $translations[$lng] = [
                                'val' => $translated_value,
                                'id' => null,
                                'msg' => sprintf(" --> %s", $this->destination_fields[$lng]['name'])
                            ];
                            $meta_to_add[$destination_field_id][] = $translated_value;
                        }
                        else {
                            $translations[$lng] = [
                                'val' => $translated_value,
                                'id' => $destination_meta_id,
                                'msg' => sprintf("already in %s", $this->destination_fields[$lng]['name'])
                            ];
                            unset($meta_to_delete[$destination_meta_id]);
                        }
                        unset($destination_meta_id);
                    }
                    unset($lng, $destination_field_id, $translated_value);
                }
                unset($nodes, $node, $tbranch);
            }
            unset($q);

            // cleanup source
            //
            if (empty($translations)) {
                $this->output->writeln(sprintf(" - \"%s\" : no translation found.", $source_value));
            }
            else if (count($translations) < count($this->destination_fields)) {
                $this->output->writeln(sprintf(" - \"%s\" : incomplete translation.", $source_value));
            }
            else {
                // complete translation (all target lng)
                $this->output->writeln(sprintf(" - \"%s\" :", $source_value));
                if ($this->cleanupSource === self::CLEANUP_SOURCE_IF_TRANSLATED) {
                    // do NOT delete the source term if one translation found it as already present as destination (possible if source=destination)
                    $used = false;
                    foreach($translations as $l => $t) {
                        if($t['id'] === $source_meta_id) {
                            $used = true;
                            break;
                        }
                    }
                    if(!$used) {
                        $meta_to_delete[$source_meta_id] = $metas[$source_field_id][$source_meta_id];
                    }
                }
            }

            foreach ($translations as $lng => $translation) {
                $this->output->writeln(sprintf("   - [%s] \"%s\" %s", $lng, $translation['val'], $translation['msg']));
            }

            if ($this->cleanupSource === self::ALWAYS_CLEANUP_SOURCE) {
                // do NOT delete the source term if one translation found it as already present as destination (possible if source=destination)
                $used = false;
                foreach($translations as $l => $t) {
                    if($t['id'] === $source_meta_id) {
                        $used = true;
                        break;
                    }
                }
                if(!$used) {
                    $meta_to_delete[$source_meta_id] = $metas[$source_field_id][$source_meta_id];
                }
            }

            unset($lng, $translations, $translation);
        }

        unset($metas, $source_meta_id, $source_value);

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
        $jsActions = json_encode($actions, JSON_PRETTY_PRINT);
 //       $this->output->writeln(sprintf("<info>JS : %s</info>", $jsActions));

        if (!$this->globalConfiguration->isDryRun()) {
            $record = $this->getDatabox()->getRecordRepository()->find($record_id);
            $record->setMetadatasByActions(json_decode($jsActions));
        }

    }

    private function splitTermAndContext($word)
    {
        $term = trim($word);
        $context = '';
        if (($po = strpos($term, '(')) !== false) {
            if (($pc = strpos($term, ')', $po)) !== false) {
                $context = trim(substr($term, $po + 1, $pc - $po - 1));
                $term = trim(substr($term, 0, $po));
            }
            else {
                $context = trim(substr($term, $po + 1));
                $term = trim(substr($term, 0, $po));
            }
        }

        return [$term, $context];
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


}
