<?php

namespace Alchemy\Phrasea\Command\Thesaurus\Translator;

use databox_field;
use DOMElement;
use DOMNode;
use DOMNodeList;
use Symfony\Component\Console\Output\OutputInterface;
use thesaurus;
use unicode;

class Action
{
    const NEVER_CLEANUP_SOURCE = 'never';
    const ALWAYS_CLEANUP_SOURCE = 'always';
    const CLEANUP_SOURCE_IF_TRANSLATED = 'if_translated';

    /** @var Job */
    private $job;

    /** @var unicode */
    private $unicode;

    /** @var OutputInterface */
    private $output;

    /** @var string  */
    private $reportFormat;


    private $active = true;
    private $errors = [];
    /** @var databox_field|null  */
    private $source_field;
    /** @var databox_field[]  */
    private $destination_fields;     // infos about the "destination_fields" (key=lng)
    /** @var array  list of field ids of "source_field" (unique) and "destination_fields" (many) */
    private $selectRecordFieldIds;
    /** @var bool */
    private $cleanupDestination;

    /** @var string */
    private $cleanupSource = self::NEVER_CLEANUP_SOURCE;



    /** @var DOMNodeList The thesaurus branch(es) linked to the "source_field" */
    private $tbranches;

    public function __construct(Job $job, $action_conf, Unicode $unicode, OutputInterface $output)
    {
        $this->job = $job;
        $this->unicode = $unicode;
        $this->output = $output;
        $this->reportFormat = $this->job->getGlobalConfiguration()->getReportFormat();

        if (array_key_exists('active', $action_conf) && $action_conf['active'] === false) {
            $this->active = false;
            return;
        }


        // get infos about the "source_field"
        //
        if (!($f = $job->getDataboxField($action_conf['source_field'])) ) {
            $this->errors[] = sprintf("source field (%s) not found.", $action_conf['source_field']);
        }
        if (trim($f->get_tbranch()) === '') {
            $this->errors[] = sprintf("source field (%s) not linked to thesaurus.", $action_conf['source_field']);
        }
        $this->tbranches = $job->getXpathTh()->query($f->get_tbranch());
        if (!$this->tbranches || $this->tbranches->length <= 0) {
            $this->errors[] = sprintf("thesaurus branch(es) of source field (%s) not found.", $this->source_field['tbranch']);
        }
        $this->source_field = [
            'id' => $f->get_id(),
            'name' => $f->get_name(),
            'tbranch' => $f->get_tbranch(),
            'lng' => array_key_exists('source_lng', $action_conf) ? $action_conf['source_lng'] : null
        ];
        $this->selectRecordFieldIds[] = $this->source_field['id'];


        // get infos about the "destination_fields"
        //
        $this->destination_fields = [];
        foreach ($action_conf['destination_fields'] as $tf) {
            list($lng, $fname) = explode(':', $tf);
            if(!($f = $job->getDataboxField($fname)) ) {
                $this->output->writeln(sprintf("<warning>undefined field (%s)  (ignored).</warning>", $fname));
                continue;
            }
            $this->destination_fields[$lng] = [
                'id' => $f->get_id(),
                'name' => $f->get_name(),
            ];

            $this->selectRecordFieldIds[] = $this->destination_fields[$lng]['id'];
        }

        if (empty($this->destination_fields)) {
            $this->errors[] = sprintf("no \"destination_field\" found.");
        }

        // misc settings
        $this->cleanupDestination = array_key_exists('cleanup_destination', $action_conf) && $action_conf['cleanup_destination'] === true;
        $this->cleanupSource = array_key_exists('cleanup_source', $action_conf) ? $action_conf['cleanup_source'] : self::NEVER_CLEANUP_SOURCE;
    }

    public function doAction(array $metas, array &$meta_to_delete, array&$meta_to_add)
    {
        if ($this->cleanupDestination) {
            foreach ($this->destination_fields as $lng => $destination_field) {
                $destination_field_id = $destination_field['id'];
                if(array_key_exists($destination_field_id, $metas)) {
                    foreach ($metas[$destination_field_id] as $meta_id => $value) {
                        $meta_to_delete[$meta_id] = $value;
                    }
                }
                unset($meta_id, $value);
            }
            unset($lng, $destination_field, $destination_field_id);
        }

        $source_field_id = $this->source_field['id'];

        if(!array_key_exists($source_field_id, $metas)) {
            // no source field value for this record: nothing to do
            return;
        }

        // loop on every value of the "source_field"
        //
        foreach ($metas[$source_field_id] as $source_meta_id => $source_value) {

            $t = $this->splitTermAndContext($source_value);
            $q = '@w=\'' . thesaurus::xquery_escape($this->unicode->remove_indexer_chars($t[0])) . '\'';
            if ($t[1]) {
                $q .= ' and @k=\'' . thesaurus::xquery_escape($this->unicode->remove_indexer_chars($t[1])) . '\'';
            }
            if(!is_null($this->source_field['lng'])) {
                $q .= ' and @lng=\'' . thesaurus::xquery_escape($this->source_field['lng']) . '\'';
            }
            $q = '//sy[' . $q . ']/../sy';
            unset($t);

            // loop on every tbranch (one field may be linked to many branches)
            //
            $translations = [];             // ONE translation per lng (first found in th)
            /** @var DOMNode $tbranch */
            foreach ($this->tbranches as $tbranch) {
                if (!($nodes = $this->job->getXpathTh()->query($q, $tbranch))) {
                    $this->output->writeln(sprintf("\t\t\t- \"%s\"  <warning>xpath error on (%s), ignored.</warning>", $source_value, $q));
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
                        if (
                            !array_key_exists($destination_field_id, $metas)
                            || ($destination_meta_id = array_search($translated_value, $metas[$destination_field_id])) === false
                        ) {
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
                if($this->reportFormat === GlobalConfiguration::REPORT_FORMAT_ALL) {
                    $this->output->writeln(sprintf("\t\t\t- \"%s\" : no translation found.", $source_value));
                }
                $this->job->addToCondensedReport($source_value, job::CONDENSED_REPORT_NOT_TRANSLATED);
            }
            else if (count($translations) < count($this->destination_fields)) {
                if(in_array($this->reportFormat, [GlobalConfiguration::REPORT_FORMAT_ALL, GlobalConfiguration::REPORT_FORMAT_TRANSLATED])) {
                    $this->output->writeln(sprintf("\t\t\t- \"%s\" : incomplete translation.", $source_value));
                }
                $this->job->addToCondensedReport($source_value, job::CONDENSED_REPORT_INCOMPLETELY_TRANSLATED);
            }
            else {
                // complete translation (all target lng)
                if(in_array($this->reportFormat, [GlobalConfiguration::REPORT_FORMAT_ALL, GlobalConfiguration::REPORT_FORMAT_TRANSLATED])) {
                    $this->output->writeln(sprintf("\t\t\t- \"%s\" :", $source_value));
                }
                $this->job->addToCondensedReport($source_value, job::CONDENSED_REPORT_FULLY_TRANSLATED);

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

            if(in_array($this->reportFormat, [GlobalConfiguration::REPORT_FORMAT_ALL, GlobalConfiguration::REPORT_FORMAT_TRANSLATED])) {
                foreach ($translations as $lng => $translation) {
                    $this->output->writeln(sprintf("\t\t\t\t- [%s] \"%s\" %s", $lng, $translation['val'], $translation['msg']));
                }
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
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getSelectRecordFieldIds(): array
    {
        return $this->selectRecordFieldIds;
    }

}
