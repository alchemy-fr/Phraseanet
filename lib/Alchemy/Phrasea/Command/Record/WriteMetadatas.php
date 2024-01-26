<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Record;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefRepository;
use Alchemy\Phrasea\Databox\SubdefGroup;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WriteMetadatas extends Command
{
    const OPTION_DISTINT_VALUES = 0;
    const OPTION_ALL_VALUES     = 1;

    /** @var InputInterface */
    private $input;
    /** @var OutputInterface */
    private $output;
    /** @var \Databox */
    private $databox;
    /** @var  connection */
    private $connection;

    /** @var int */
    private $min_record_id;
    /** @var int */
    private $max_record_id;
    /** @var bool */
    private $reverse;
    /** @var bool */
    private $dry;
    /** @var bool */
    private $show_sql;

    /** @var  array */
    private $recordTypes;

    /** @var array */
    private $fileTypes;
    /** @var array */
    private $names;
    /** @var array */
    private $subdefAcceptedMimeTypes;


    public function __construct()
    {
        parent::__construct('records:writemetadatas');

        $this->setDescription("Publish a message triggering metadata written in the record's document or subdefinitions");
        $this->addOption('databox',            null, InputOption::VALUE_REQUIRED,                             'Mandatory : The id (or dbname or viewname) of the databox');
        $this->addOption('record_type',        null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Type(s) of records(s) to write metadata ex. "image,video", default=ALL');
        $this->addOption('min_record_id',      null, InputOption::VALUE_OPTIONAL,                             'Min record id');
        $this->addOption('max_record_id',      null, InputOption::VALUE_OPTIONAL,                             'Max record id');
        $this->addOption('reverse',            null, InputOption::VALUE_NONE,                                 'Get records from the last to the oldest');
        $this->addOption('name',               null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Name(s) of sub-definition(s) to write metadatas, ex. "thumbnail,preview", default=ALL');
        $this->addOption('file_type',          null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Mimetype of the record to write metadatas, ex. "image/tiff,image/jpeg", default=ALL');
        $this->addOption('dry',                null, InputOption::VALUE_NONE,                                 'dry run, list but don\'t act');
        $this->addOption('show_sql',           null, InputOption::VALUE_NONE,                                 'show sql pre-selecting records');

        $this->setHelp("Publish a message triggering metadata written in the record's document or subdefinitions, subdefinition settings and mime type in configuration.yml is taken into account (Beta)\n\n"
            . "Record filters :\n"
            . " --record_type=image,video : Select records of those types ('image','video','audio','document').\n"
            . " --file_type=image/tiff,image/jpeg : Select records with those mimetypes.\n"
            . " --min_record_id=100       : Select records with record_id >= 100.\n"
            . " --max_record_id=500       : Select records with record_id <= 500.\n"
            . "Subdef filters :\n"
            . " --name=thumbnail,preview  : write metadata only in thumbnail and preview.\n"
        );

    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if(!$this->sanitizeArgs($input, $output)) {
            return -1;
        }

        // get the whiteliste mimetype in which we can write metadata
        $this->subdefAcceptedMimeTypes = $this->container['conf']->get(['workers', 'writeMetadatas', 'acceptedMimeType'], []);

        $this->input  = $input;
        $this->output = $output;

        $sql = $this->getSQL();

        if($this->show_sql) {
            $this->output->writeln($sql);
        }

        $sqlCount = sprintf('SELECT COUNT(*) FROM (%s) AS c', $sql);

        $totalRecords = (int)$this->connection->executeQuery($sqlCount)->fetchColumn();

        $nbWriteMetaPublish = 0;

        $stmt = $this->connection->executeQuery($sql);

        $mediaSubdefRepository = $this->getMediaSubdefRepository($this->databox->get_sbas_id());

        $output->writeln(sprintf('<info> %s records found to write metadatas in subdefinition files!</info>', $totalRecords));

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $record = $this->databox->get_record($row['record_id']);
            $mediaSubdefs = $mediaSubdefRepository->findByRecordIdsAndNames([$row['record_id']]);

            // it's sure it's not a story
            // the record type also yet checked from sql
            $type    = $record->getType();
            $subdefGroupe = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType());

            // check config admin for the subdef group
            if ($subdefGroupe !== null) {
                $toWritemetaOriginalDocument = $subdefGroupe->toWritemetaOriginalDocument();
            } else {
                $toWritemetaOriginalDocument = true;
            }

            foreach ($mediaSubdefs as $subdef) {
                $subdefName = $subdef->get_name();
                // check if a specific subview name to write metadata is given
                if (!empty($this->names) && !in_array($subdefName, $this->names)) {
                    // not found
                    continue;
                }

                // check subdefmetadatarequired  from the subview setup in admin
                // check if we want to write meta in this mime type
                if (in_array(trim($subdef->get_mime()), $this->subdefAcceptedMimeTypes) &&
                    (
                        ($subdef->get_name() == 'document' && $toWritemetaOriginalDocument) ||
                        $this->isSubdefMetadataUpdateRequired($this->databox, $type, $subdef->get_name())
                    )
                ) {
                    $payload = [
                        'message_type' => MessagePublisher::WRITE_METADATAS_TYPE,
                        'payload' => [
                            'recordId'    => $row['record_id'],
                            'databoxId'   => $record->getDataboxId(),
                            'subdefName'  => $subdef->get_name()
                        ]
                    ];
                    if ($subdef->is_physically_present()) {
                        $nbWriteMetaPublish++;
                        if (!$this->dry) {
                            $this->getMessagePublisher()->publishMessage($payload, MessagePublisher::WRITE_METADATAS_TYPE);
                        }
                    }
                }
            }
        }

        unset($stmt);

        $output->writeln(sprintf('<info> %s subdefinitions files to process writemetadatas in the worker</info>', $nbWriteMetaPublish));

        if ($this->dry) {
            $output->writeln("we are in dry mode, message is not publish to the worker queue");
        }

        return 0;
    }


    /**
     * sanity check the cmd line options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function sanitizeArgs(InputInterface $input, OutputInterface $output)
    {
        $argsOK = true;

        // find the databox / collection by id or by name
        $this->databox = null;
        if (($d = $input->getOption('databox')) !== null) {
            $d = trim($d);
            foreach ($this->container->getDataboxes() as $db) {
                if ($db->get_sbas_id() == (int)$d || $db->get_viewname() == $d || $db->get_dbname() == $d) {
                    $this->databox = $db;
                    $this->connection = $db->get_connection();
                    break;
                }
            }
            if ($this->databox == null) {
                $output->writeln(sprintf("<error>Unknown databox \"%s\"</error>", $input->getOption('databox')));
                $argsOK = false;
            }
        } else {
            $output->writeln(sprintf("<error>Missing mandatory options --databox</error>"));
            $argsOK = false;
        }

        // get options

        $this->show_sql           = $input->getOption('show_sql') ? true : false;
        $this->dry                = $input->getOption('dry') ? true : false;
        $this->min_record_id      = $input->getOption('min_record_id');
        $this->max_record_id      = $input->getOption('max_record_id');
        $this->reverse            = $input->getOption('reverse') ? true : false;

        $types              = $this->getOptionAsArray($input, 'record_type', self::OPTION_DISTINT_VALUES);
        $this->names        = $this->getOptionAsArray($input, 'name', self::OPTION_DISTINT_VALUES);
        $this->fileTypes    = $this->getOptionAsArray($input, 'file_type', self::OPTION_DISTINT_VALUES);

        // validate types
        $this->recordTypes = [];

        if ($this->databox !== null) {
            /** @var SubdefGroup $sg */
            foreach ($this->databox->get_subdef_structure() as $sg) {
                if (empty($types) || in_array($sg->getName(), $types)) {
                    $this->recordTypes[] = $sg->getName();
                }
            }
            foreach ($types as $t) {
                if (!in_array($t, $this->recordTypes)) {
                    $output->writeln(sprintf("<error>unknown type \"%s\"</error>", $t));
                    $argsOK = false;
                }
            }
        }

        return $argsOK;
    }

    /**
     * merge options so one can mix csv-option and/or multiple options
     * ex. with keepUnique = false :  --opt=a,b --opt=c --opt=b  ==> [a,b,c,b]
     * ex. with keepUnique = true  :  --opt=a,b --opt=c --opt=b  ==> [a,b,c]
     *
     * @param InputInterface $input
     * @param string $optionName
     * @param int $option
     * @return array
     */
    private function getOptionAsArray(InputInterface $input, $optionName, $option)
    {
        $ret = [];
        foreach($input->getOption($optionName) as $v0) {
            foreach(explode(',', $v0) as $v) {
                $v = trim($v);
                if($option & self::OPTION_ALL_VALUES || !in_array($v, $ret)) {
                    $ret[] = $v;
                }
            }
        }

        return $ret;
    }

    /**
     * @return string
     */
    private function getSQL()
    {
        $sql = "SELECT r.`record_id`\n";

        $sql .= "FROM `record` AS r\n"
             . "WHERE r.`parent_record_id`=0\n";

        $recordTypes = array_map(function($v) {return $this->connection->quote($v);}, $this->recordTypes);

        $fileTypes = array_map(function($v) {return $this->connection->quote($v);}, $this->fileTypes);

        if (!empty($recordTypes)) {
            $sql .= " AND r.`type` IN(" . implode(',', $recordTypes) . ")\n";
        }

        if ($this->min_record_id !== null) {
            $sql .= " AND (r.`record_id` >= " . (int)($this->min_record_id) . ")\n";
        }

        if ($this->max_record_id) {
            $sql .= " AND (r.`record_id` <= " . (int)($this->max_record_id) . ")\n";
        }

        if(!empty($fileTypes)) {
            $sql .= " AND r.`mime` IN(" . implode(',', $fileTypes) . ")\n";
        }

        $sql .= "GROUP BY r.`record_id`";

        $sql .= "\nORDER BY r.`record_id` " . ($this->reverse ? "DESC" : "ASC");

        return $sql;
    }

    /**
     * @param $databoxId
     *
     * @return MediaSubdefRepository
     */
    private function getMediaSubdefRepository($databoxId)
    {
        return $this->container['provider.repo.media_subdef']->getRepositoryForDatabox($databoxId);
    }

    /**
     * @return MessagePublisher
     */
    private function getMessagePublisher()
    {
        return  $this->container['alchemy_worker.message.publisher'];
    }

    /**
     * @param \databox $databox
     * @param string $subdefType
     * @param string $subdefName
     * @return bool
     */
    private function isSubdefMetadataUpdateRequired(\databox $databox, $subdefType, $subdefName)
    {
        if ($databox->get_subdef_structure()->hasSubdef($subdefType, $subdefName)) {
            return $databox->get_subdef_structure()->get_subdef($subdefType, $subdefName)->isMetadataUpdateRequired();
        }

        return false;
    }
}
