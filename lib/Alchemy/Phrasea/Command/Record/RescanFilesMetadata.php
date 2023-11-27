<?php

namespace Alchemy\Phrasea\Command\Record;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RescanFilesMetadata extends Command
{
    private $databoxes = [];

    /** @var \appbox $appbox */
    private $appbox;

    /** @var PhraseanetExtension */
    private $helpers;

    /** @var \unicode */
    private $unicode;

    public function __construct()
    {
        parent::__construct('records:rescan-files-metadata');

        $this
            ->setDescription('Read metadata from file and add it to field')
            ->addOption('databox', null, InputOption::VALUE_REQUIRED,  ' The id (or dbname) of the databox')
            ->addOption('collection', null, InputOption::VALUE_REQUIRED,  ' The baseid (or name) of the collection')
            ->addOption('max_record_id', null, InputOption::VALUE_REQUIRED, "highest record_id value")
            ->addOption('min_record_id', null, InputOption::VALUE_REQUIRED, "lowest record_id value")
            ->addOption('record_type',        null, InputOption::VALUE_REQUIRED, 'Type of records(s) to scan.')
            ->addOption('partition',          null, InputOption::VALUE_REQUIRED, 'n/N : work only on records belonging to partition')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'tag to search exemple IPTC:KEYWORD')
            ->addOption('destination', null, InputOption::VALUE_REQUIRED, "ID of the field de fill")
            ->addOption('overwrite', null, InputOption::VALUE_NONE, "act even if the destination field has a value in databox")
            ->addOption('multi', null, InputOption::VALUE_REQUIRED, "replace or merge for multi value field")
            ->addOption('dry', null, InputOption::VALUE_NONE, "Dry run (list alert only and list record and meta values).")
        ;

        $this->setHelp(
            " --partition=2/5    : Split databox records in 5 buckets, select records in bucket #2.\n"
        );
    }
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->appbox = $this->container['phraseanet.appbox'];

        $this->resolveName();

        $this->helpers = new PhraseanetExtension($this->container);

        $source  = $sourceTag = $input->getOption('source');
        $destination = $input->getOption('destination');
        $type = $input->getOption('record_type');
        $col = $input->getOption('collection');
        $multi = $input->getOption('multi');
        $overwrite = $input->getOption('overwrite');
        $dry = $input->getOption('dry');

        if ($destination == NULL) {
            $output->writeln("destination is mandatory");

            return 0;
        }

        // validate partition
        $partitionIndex = $partitionCount = null;
        if( ($arg = $input->getOption('partition')) !== null) {
            $arg = explode('/', $arg);
            if(count($arg) == 2 && ($arg0 = (int)trim($arg[0]))>0 && ($arg1 = (int)trim($arg[1]))>1 && $arg0<=$arg1 ) {
                $partitionIndex = $arg0;
                $partitionCount = $arg1;
            }
            else {
                $output->writeln(sprintf('<error>partition must be n/N</error>'));

                return 0;
            }
        }

        if ($multi != null && !in_array($multi, ['replace', 'merge'])) {
            $output->writeln("<error> wrong value for --multi, use replace or merge</error>");

            return 0;
        }

        $maxRecord = $input->getOption("max_record_id");
        $minRecord = $input->getOption("min_record_id");

        if ($input->getOption("databox") != NULL) {
            try {
                $db = $this->getDatabox($input->getOption("databox"));
                if ($db == null) {
                    $output->writeln("<error>databox not found</error>");

                    return 0;
                }
                $dboxes = [$db];
            } catch (\Exception $e) {
                $dboxes = [];
            }
        } else {
            $dboxes = $this->appbox->get_databoxes();
        }

        //  sql request
        $clauses[]= '1';
        if ($minRecord != NULL) {
            $clauses[] = "record_id >= " . $minRecord;
        }

        if ($maxRecord != NULL) {
            $clauses[] = "record_id <= " . $maxRecord;
        }

        if ($type != null) {
            $clauses[] = "type = " . $type;
        }

        if($partitionCount !== null && $partitionIndex !== null) {
            $clauses[] = " MOD(`record_id`, " . $partitionCount . ")=" . ($partitionIndex-1);
        }

        ///

        $this->unicode = new \unicode();

        foreach ($dboxes as $databox) {
            $sbasId = $databox->get_sbas_id();
            $sbasName = $databox->get_dbname();
            $rToDoCount = $rDoneCount = 0;

            $field = $this->getField($sbasId, $destination);

            if ($field == null) {
                $output->writeln(sprintf("<error>Field %s not found on database %s </error>", $destination, $sbasName));
                continue;
            }

            if ( $col != null) {
                $collection = $this->getCollection($sbasId, $col);
                if ($collection == null) {
                    $output->writeln(sprintf("<error>collection %s not found on database %s </error>", $col, $sbasName));
                    continue;
                }

                $collId = $collection->get_coll_id();

                $clauses[] = "coll_id = " . $collId;
            }

            if ($source == null) {
                // use the destination field src
                $sourceTag = $field->get_original_source();
            }

            $metaStructId = $field->get_id();
            $fieldName = $field->get_name();
            $fieldType = $field->get_type();

            $action = "set";

            if ($field->is_multi()) {
                $action = 'add';

                if ($multi == 'replace') {
                    $action = 'replace';
                }
            }

            $output->writeln(sprintf("<comment>Working on database %s with Id : %d</comment>", $sbasName, $sbasId));


            $sql_where = join(" AND ", $clauses);

            $sql = "SELECT * FROM record WHERE " . $sql_where;

            $stmt = $databox->get_connection()->prepare($sql);

            if ($dry) {
                $output->writeln("<info>" . $sql . "</info>");
            }

            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $record = $databox->get_record($row['record_id']);

                // skip non empty field if overwrite is not set and not a merge multivalued field
                if ($overwrite == null && $action != 'add' && !$this->isEmptyField($record, $fieldName)) {
                    continue;
                }

                try {
                    $results = $record->getFileMetadataByTag($sourceTag);
                    $rToDoCount ++;
                } catch (\Exception $e) {
                    $output->writeln("<error> can not get metadata into the document file : " . $e->getMessage() . "</error>");
                    $results = [];
                }

                if ($results != NULL) {
                    $metadatas = [];

                    // for multi valued field
                    if ($action == 'replace') {
                        $metadatas[] = [
                            'meta_struct_id' => $metaStructId,
                            'meta_id'        => null,
                            'value'          => $this->sanitizeValue($results, $fieldType)
                        ];
                    } else {
                        foreach ($results as $result) {
                            $metadatas[] = [
                                'action'         => $action,
                                'meta_struct_id' => $metaStructId,
                                'meta_id'        => null,
                                'value'          => $this->sanitizeValue($result, $fieldType)
                            ];
                        }
                    }

                    $actions['metadatas'] = $metadatas;

                    if ($dry) {
                        print_r($actions);
                    } else {
                        $record->setMetadatasByActions(json_decode(json_encode($actions)));
                        $rDoneCount ++;
                    }
                }
            }

            $output->writeln(sprintf("<info>%d records scaned and %d record metadata updated on database %s</info>", $rToDoCount, $rDoneCount, $sbasName));
        }

        return 0;
    }

    private function sanitizeValue($values, $type)
    {
        $isValueArray = true;
        if (!is_array($values)) {
            $isValueArray = false;
            $values = [$values];
        }

        $v = [];
        foreach ($values as $value) {
            $value = $this->unicode->substituteCtrlCharacters($value, ' ');
            $value = $this->unicode->toUTF8($value);
            if ($type == 'date') {
                $value = $this->unicode->parseDate($value);
            }
            $v[] = $value;
        }

        return $isValueArray ? $v : current($v);
    }

    private function resolveName()
    {
        // list databoxes and collections to access by id or by name
        $this->databoxes = [];

        foreach ($this->appbox->get_databoxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();
            $sbas_name = $databox->get_dbname();
            $this->databoxes[$sbas_id] = [
                'dbox' => $databox,
                'collections' => [],
                'fields' => [],
            ];
            $this->databoxes[$sbas_name] = &$this->databoxes[$sbas_id];
            // list all collections
            foreach ($databox->get_collections() as $collection) {
                $baseId = $collection->get_base_id();
                $coll_name = $collection->get_name();
                $this->databoxes[$sbas_id]['collections'][$baseId] = $collection;
                $this->databoxes[$sbas_id]['collections'][$coll_name] = &$this->databoxes[$sbas_id]['collections'][$baseId];
            }
            // list all fields
            /** @var \databox_field $dbf */
            foreach($databox->get_meta_structure() as $dbf) {
                $field_id = $dbf->get_id();
                $field_name = $dbf->get_name();
                $this->databoxes[$sbas_id]['fields'][$field_id] = $dbf;
                $this->databoxes[$sbas_id]['fields'][$field_name] = &$this->databoxes[$sbas_id]['fields'][$field_id];
            }
        }
    }

    private function isEmptyField(\record_adapter $r, $fieldName)
    {
        if ($r->get_caption()->has_field($fieldName)) {
            // normally if there is no value, there is no caption field
            $captionField =  $r->get_caption()->get_field($fieldName);
            $fieldValues = $captionField->get_values();

            if (empty($this->helpers->getCaptionField($r, $fieldName, $fieldValues))) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * @param string|int $sbasIdOrName
     * @return \databox|null
     */
    private function getDatabox($sbasIdOrName)
    {
        return isset($this->databoxes[$sbasIdOrName]) ? $this->databoxes[$sbasIdOrName]['dbox'] : null;
    }

    /**
     * @param string|int $sbasIdOrName
     * @param string|int $collIdOrName
     * @return \collection|null
     */
    private function getCollection($sbasIdOrName, $collIdOrName)
    {
        return $this->databoxes[$sbasIdOrName]['collections'][$collIdOrName] ?? null;
    }

   /**
     * @param string|int $sbasIdOrName
     * @param string|int $collIdOrName
     * @return \databox_field|null
     */
    private function getField($sbasIdOrName, $fieldIdOrName)
    {
        return $this->databoxes[$sbasIdOrName]['fields'][$fieldIdOrName] ?? null;
    }
}
