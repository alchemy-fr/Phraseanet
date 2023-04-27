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
    const ORIGINAL = 'original';
    const COMPLETE = 'complete';
    const INCOMPLETE = 'incomplete';
    const NOT_TRANSLATED = 'not_translated';
    const NEVER_CLEANUP_SOURCE = 'never';
    const ALWAYS_CLEANUP_SOURCE = 'always';
    const CLEANUP_SOURCE_IF_TRANSLATED = 'if_translated';
    const TO_BE_DELETED = 'to_be_deleted';

    private $active = true;

    /** @var string[] */
    private $errors = [];       // error messages while parsing conf

    /** @var databox|null $databox */
    private $databox = null;

    /** @var array */
    private $selectRecordParams = [];

    private $selectRecordsSql = null;

    /** @var array  list of field ids of "fromField" (unique) and "toFields" (many) */
    private $selectRecordFieldIds;

    /**
     * @var OutputInterface
     */
    private $output;

    private $from_field;    // infos about the "from_field"
    private $to_fields;     // infos about the "to_fields" (key=lng)

    /**
     * @var Unicode
     */
    private $unicode;

    /** @var DOMXpath|false|thesaurus_xpath */
    private $xpathTh;

    /**
     * @var DOMNodeList
     * The thesaurus branch(es) linked to the "from_field"
     */
    private $tbranches;

    /** @var bool */
    private $cleanupDestination;

    /** @var string  */
    private $cleanupSource = self::NEVER_CLEANUP_SOURCE;

    /**
     * @param GlobalConfiguration $globalConfiguration
     * @param array $job_conf
     */
    public function __construct($globalConfiguration, $job_conf, Unicode $unicode, OutputInterface $output)
    {
        $this->output = $output;
        $this->unicode = $unicode;

        if (array_key_exists('active', $job_conf) && $job_conf['active'] === false) {
            $this->active = false;

            return;
        }

        $this->errors = [];
        foreach (['from_databox', 'from_field', 'from_lng'] as $mandatory) {
            if (!isset($job_conf[$mandatory])) {
                $this->errors[] = sprintf("Missing mandatory setting (%s).", $mandatory);
            }
        }
        if (!empty($this->errors)) {
            return;
        }

        if (!($this->databox = $globalConfiguration->getDatabox($job_conf['from_databox']))) {
            $this->errors[] = sprintf("unknown databox (%s).", $job_conf['from_databox']);

            return;
        }

        $cnx = $this->databox->get_connection();

        // get infos about the "from_field"
        //
        $sql = "SELECT `id`, `tbranch` FROM `metadatas_structure` WHERE `name` = :name AND `tbranch` != ''";
        $stmt = $cnx->executeQuery($sql, [':name' => $job_conf['from_field']]);
        $this->from_field = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$this->from_field) {
            $this->errors[] = sprintf("field (%s) not found or not linked to thesaurus.", $job_conf['from_field']);

            return;
        }
        $this->from_field['lng'] = $job_conf['from_lng'];
        $this->selectRecordFieldIds[] = $this->from_field['id'];
        $this->xpathTh = $this->databox->get_xpath_thesaurus();
        $this->tbranches = $this->xpathTh->query($this->from_field['tbranch']);
        if (!$this->tbranches || $this->tbranches->length <= 0) {
            $this->errors[] = sprintf("thesaurus branch(es) (%s) not found.", $this->from_field['tbranch']);

            return;
        }

        // get infos about the "to_fields"
        //
        $this->to_fields = [];
        $sql = "SELECT `id`, `name` FROM `metadatas_structure` WHERE `name` = :name ";
        $stmt = $cnx->prepare($sql);
        foreach ($job_conf['to_fields'] as $tf) {
            list($lng, $fname) = explode(':', $tf);
            $stmt->execute([':name' => $fname]);
            if (!($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
                $this->output->writeln(sprintf("<warning>undefined field (%s)  (ignored).</warning>", $fname));
                continue;
            }
            $this->to_fields[$lng] = $row;
            $stmt->closeCursor();

            $this->selectRecordFieldIds[] = $row['id'];
        }

        if (empty($this->to_fields)) {
            $this->errors[] = sprintf("<warning>no \"to_field\" found.</warning>");

            return;
        }

        // misc settings
        $this->cleanupDestination = array_key_exists('cleanup_destination', $job_conf) && $job_conf['cleanup_destination'] === true;
        $this->cleanupSource = array_key_exists('cleanup_source', $job_conf) ? $job_conf['cleanup_source'] : self::NEVER_CLEANUP_SOURCE;

        // build records select sql
        //
        $selectRecordClauses = [];
        $this->selectRecordParams = [];
        if (array_key_exists('from_collection', $job_conf)) {
            if (!($coll = $globalConfiguration->getCollection($job_conf['from_databox'], $job_conf['from_collection']))) {
                $this->errors[] = sprintf("unknown collection (%s)", $job_conf['from_collection']);

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

//        if ($this->cleanupDestination) {
//            // if we must empty the destination field(s), no need to get the values
//            $selectRecordClauses[] = "`meta_struct_id` = :ffid";
//            $this->selectRecordParams[':ffid'] = $this->from_field['id'];
//        }
//        else {
            // if we add translations, we must fetch the actual values
            $selectRecordClauses[] = "`meta_struct_id` IN (" . join(',', array_map(function ($id) use ($cnx) {
                    return $cnx->quote($id);
                }, $this->selectRecordFieldIds)) . ")";
//        }

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

            $metas[$row['meta_struct_id']][$row['meta_id']] = ['value' => $row['value'], 'status' => self::ORIGINAL];
        }
        $this->doRecord($currentRid, $metas);  // flush last record

        $stmt->closeCursor();
    }

    private function doRecord($record_id, $metas)
    {
        // loop on every "from" values
        $from_field_id = $this->from_field['id'];
        $this->output->writeln(sprintf("record id: %s", $record_id));

        // loop on every value of the "from_field"
        //
        foreach ($metas[$from_field_id] as $kmeta => $meta) {
            $value = $meta['value'];
            // $this->output->write(sprintf(" - \"%s\"", $value));

            $t = $this->splitTermAndContext($value);
            $q = '@w=\'' . \thesaurus::xquery_escape($this->unicode->remove_indexer_chars($t[0])) . '\'';
            if ($t[1]) {
                $q .= ' and @k=\'' . \thesaurus::xquery_escape($this->unicode->remove_indexer_chars($t[1])) . '\'';
            }
            $q .= ' and @lng=\'' . \thesaurus::xquery_escape($this->from_field['lng']) . '\'';
            $q = '//sy[' . $q . ']/../sy';

            // loop on every tbranch (one field may be linked on many branches)
            //
            $translations = [];             // ONE translation per lng (first found in th)
            /** @var DOMNode $tbranch */
            foreach ($this->tbranches as $tbranch) {
                if (!($nodes = $this->xpathTh->query($q, $tbranch))) {
                    $this->output->writeln(sprintf(" - \"%s\"  <warning>xpath error on (%s), ignored.</warning>", $value, $q));
                    continue;
                }

                // loop on every synonym
                //
                /** @var DOMElement $node */
                foreach ($nodes as $node) {
                    $lng = $node->getAttribute('lng');

                    // ignore synonyms not in one of the "to_field" languages
                    //
                    if (!array_key_exists($lng, $this->to_fields)) {
                        continue;
                    }

                    if (empty($translations)) {
                        // first translation: begin list
                        $this->output->writeln(sprintf(" - \"%s\"", $value));
                    }

                    $to_field_id = $this->to_fields[$lng]['id'];

                    if (!array_key_exists($lng, $translations)) {
                        $translations[$lng] = $node->getAttribute('v');
                        $this->output->writeln(sprintf("   - [%s] \"%s\" --> %s", $lng, $translations[$lng], $this->to_fields[$lng]['name']));
                    }
                }
            }

            // cleanup source
            //
            if (empty($translations)) {
                $this->output->writeln(sprintf(" - \"%s\" no translation found.", $value));
                $metas[$from_field_id][$kmeta]['status'] = self::NOT_TRANSLATED;
            }
            else if (count($translations) < count($this->to_fields)) {
                $this->output->writeln(sprintf("   (incomplete translation)."));
                $metas[$from_field_id][$kmeta]['status'] = self::INCOMPLETE;
            }
            else {
                // complete translation (all target lng)
                $metas[$from_field_id][$kmeta]['status'] = self::COMPLETE;
                if($this->cleanupSource === self::CLEANUP_SOURCE_IF_TRANSLATED) {
                    $metas[$from_field_id][$kmeta]['status'] = self::TO_BE_DELETED;
                }
            }
            if($this->cleanupSource === self::ALWAYS_CLEANUP_SOURCE) {
                $metas[$from_field_id][$kmeta]['status'] = self::TO_BE_DELETED;
            }

            // add / merge translations to targets
            //
            foreach($translations as $lng => $value) {
                $to_field_id = $this->to_fields[$lng]['id'];
            }

        }

        return;
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
