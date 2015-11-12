<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\BulkOperation;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegateInterface;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\RecordListFetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\ScheduledFetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Fetcher;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\CoreHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\FlagHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\MetadataHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\SubDefinitionHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\ThesaurusHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\TitleHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordQueuer;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\StringUtils;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\CandidateTerms;
use databox;
use Iterator;
use Psr\Log\LoggerInterface;

class RecordIndexer
{
    const TYPE_NAME = 'record';

    private $structure;

    private $helper;

    private $thesaurus;

    /**
     * @var \appbox
     */
    private $appbox;

    /**
     * @var array
     */
    private $locales;

    private $logger;

    private function getUniqueOperationId($record_key)
    {
        $_key = dechex(mt_rand());
        return $_key . '_' . $record_key;
    }

    public function __construct(Structure $structure, RecordHelper $helper, Thesaurus $thesaurus, \appbox $appbox, array $locales, LoggerInterface $logger)
    {
        $this->structure = $structure;
        $this->helper = $helper;
        $this->thesaurus = $thesaurus;
        $this->appbox = $appbox;
        $this->locales = $locales;
        $this->logger = $logger;
    }

    /**
     * ES made a bulk op, check our (index) operations to drop the "indexing" & "to_index" jetons
     *
     * @param databox $databox
     * @param array $operation_identifiers  key:op_identifier ; value:operation result (json from es)
     * @param array $submited_records       records indexed, key:op_identifier
     */
    private function onBulkFlush(databox $databox, array $operation_identifiers, array &$submited_records)
    {
        // nb: because the same bulk could be used by many "clients", this (each) callback may receive
        // operation_identifiers that does not belong to it.
        // flag only records that the fetcher worked on
        $records = array_intersect_key(
            $submited_records,        // this is OUR records list
            $operation_identifiers          // reduce to the records indexed by this bulk (should be the same...)
        );
        if(count($records) === 0) {
            return;
        }
        // Commit and remove "indexing" flag
        RecordQueuer::didFinishIndexingRecords(array_values($records), $databox);
        foreach (array_keys($records) as $id) {
            unset($submited_records[$id]);
        }
    }

    /**
     * index whole databox(es), don't test actual "jetons"
     *
     * @param BulkOperation $bulk
     * @param databox[] $databoxes
     */
    public function populateIndex(BulkOperation $bulk, array $databoxes)
    {
        foreach ($databoxes as $databox) {
            $submited_records = [];

            $this->logger->info(sprintf('Indexing database %s...', $databox->get_viewname()));

            $fetcher = $this->createFetcherForDatabox($databox);    // no delegate, scan the whole records

            // post fetch : flag records as "indexing"
            $fetcher->setPostFetch(function(array $records) use ($databox, $fetcher) {
                RecordQueuer::didStartIndexingRecords($records, $databox);
                // do not restart the fetcher since it has no clause on jetons
            });

            // bulk flush : flag records as "indexed"
            $bulk->onFlush(function($operation_identifiers) use ($databox, &$submited_records) {
                $this->onBulkFlush($databox, $operation_identifiers, $submited_records);
            });

            // Perform indexing
            $this->indexFromFetcher($bulk, $fetcher, $submited_records);

            $this->logger->info(sprintf('Finished indexing %s', $databox->get_viewname()));
        }
    }

    /**
     * Index the records flagged as "to_index" on all databoxes
     *
     * @param BulkOperation $bulk
     */
    public function indexScheduled(BulkOperation $bulk)
    {
        foreach ($this->appbox->get_databoxes() as $databox) {
            $this->indexScheduledInDatabox($bulk, $databox);
        }
    }

    private function indexScheduledInDatabox(BulkOperation $bulk, databox $databox)
    {
        $submited_records = [];

        // Make fetcher
        $delegate = new ScheduledFetcherDelegate();
        $fetcher = $this->createFetcherForDatabox($databox, $delegate);

        // post fetch : flag records as "indexing"
        $fetcher->setPostFetch(function(array $records) use ($databox, $fetcher) {
            RecordQueuer::didStartIndexingRecords($records, $databox);
            // because changing the flag on the records affects the "where" clause of the fetcher,
            // restart it each time
            $fetcher->restart();
        });

        // bulk flush : flag records as "indexed"
        $bulk->onFlush(function($operation_identifiers) use ($databox, &$submited_records) {
            $this->onBulkFlush($databox, $operation_identifiers, $submited_records);
        });

        // Perform indexing
        $this->indexFromFetcher($bulk, $fetcher, $submited_records);
    }

    /**
     * Index a list of records
     *
     * @param BulkOperation $bulk
     * @param Iterator $records
     */
    public function index(BulkOperation $bulk, Iterator $records)
    {
        foreach ($this->createFetchersForRecords($records) as $fetcher) {
            $submited_records = [];
            $databox = $fetcher->getDatabox();

            // post fetch : flag records as "indexing"
            $fetcher->setPostFetch(function(array $records) use ($fetcher, $databox) {
                RecordQueuer::didStartIndexingRecords($records, $databox);
                // do not restart the fetcher since it has no clause on jetons
            });

            // bulk flush : flag records as "indexed"
            $bulk->onFlush(function($operation_identifiers) use ($databox, &$submited_records) {
                $this->onBulkFlush($databox, $operation_identifiers, $submited_records);
            });

            // Perform indexing
            $this->indexFromFetcher($bulk, $fetcher, $submited_records);
        }
    }

    /**
     * Deleta a list of records
     *
     * @param BulkOperation $bulk
     * @param Iterator $records
     */
    public function delete(BulkOperation $bulk, Iterator $records)
    {
        foreach ($records as $record) {
            $params = array();
            $params['id'] = $record->getId();
            $params['type'] = self::TYPE_NAME;
            $bulk->delete($params, null);       // no operationIdentifier is related to a delete op
        }
    }

    /**
     * @param Iterator $records
     * @return Fetcher[]
     */
    private function createFetchersForRecords(Iterator $records)
    {
        $fetchers = array();
        foreach ($this->groupRecordsByDatabox($records) as $group) {
            $databox = $group['databox'];
            $delegate = new RecordListFetcherDelegate($group['records']);
            $fetchers[] = $this->createFetcherForDatabox($databox, $delegate);
        }

        return $fetchers;
    }

    private function createFetcherForDatabox(databox $databox, FetcherDelegateInterface $delegate = null)
    {
        $connection = $databox->get_connection();
        $candidateTerms = new CandidateTerms($databox);
        $fetcher = new Fetcher($databox, array(
            new CoreHydrator($databox->get_sbas_id(), $databox->get_viewname(), $this->helper),
            new TitleHydrator($connection),
            new MetadataHydrator($connection, $this->structure, $this->helper),
            new FlagHydrator($this->structure, $databox),
            new ThesaurusHydrator($this->structure, $this->thesaurus, $candidateTerms),
            new SubDefinitionHydrator($connection)
        ), $delegate);
        $fetcher->setBatchSize(200);
        $fetcher->onDrain(function() use ($candidateTerms) {
            $candidateTerms->save();
        });

        return $fetcher;
    }

    private function groupRecordsByDatabox(Iterator $records)
    {
        $databoxes = array();
        foreach ($records as $record) {
            $databox = $record->get_databox();
            $hash = spl_object_hash($databox);
            $databoxes[$hash]['databox'] = $databox;
            $databoxes[$hash]['records'][] = $record;
        }

        return array_values($databoxes);
    }

    private function indexFromFetcher(BulkOperation $bulk, Fetcher $fetcher, array &$submited_records)
    {
        /** @var RecordInterface $record */
        while ($record = $fetcher->fetch()) {
            $op_identifier = $this->getUniqueOperationId($record['id']);

            $params = array();
            $params['id'] = $record['id'];
            unset($record['id']);
            $params['type'] = self::TYPE_NAME;
            $params['body'] = $record;

            $submited_records[$op_identifier] = $record;

            $bulk->index($params, $op_identifier);
        }
    }

    public function getMapping()
    {
        $mapping = new Mapping();
        $mapping
            // Identifiers
            ->add('record_id', 'integer')  // Compound primary key
            ->add('databox_id', 'integer') // Compound primary key
            ->add('databox_name', 'string')->notAnalyzed() // database name (still indexed for facets)
            ->add('base_id', 'integer') // Unique collection ID
            ->add('collection_id', 'integer')->notIndexed() // Useless collection ID (local to databox)
            ->add('collection_name', 'string')->notAnalyzed() // Collection name (still indexed for facets)
            ->add('uuid', 'string')->notIndexed()
            ->add('sha256', 'string')->notIndexed()
            // Mandatory metadata
            ->add('original_name', 'string')->notIndexed()
            ->add('mime', 'string')->notAnalyzed() // Indexed for Kibana only
            ->add('type', 'string')->notAnalyzed()
            ->add('record_type', 'string')->notAnalyzed() // record or story
            // Dates
            ->add('created_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
            ->add('updated_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
            // Thesaurus
            ->add('concept_path', $this->getThesaurusPathMapping())
            // EXIF
            ->add('metadata_tags', $this->getMetadataTagMapping())
            // Status
            ->add('flags', $this->getFlagsMapping())
            ->add('flags_bitfield', 'integer')->notIndexed()
            // Keep some fields arround for display purpose
            ->add('subdefs', Mapping::disabledMapping())
            ->add('title', Mapping::disabledMapping())
        ;

        // Caption mapping
        $this->buildCaptionMapping($this->structure->getUnrestrictedFields(), $mapping, 'caption');
        $this->buildCaptionMapping($this->structure->getPrivateFields(), $mapping, 'private_caption');

        return $mapping->export();
    }

    private function buildCaptionMapping(array $fields, Mapping $root, $section)
    {
        $mapping = new Mapping();
        foreach ($fields as $field) {
            $this->addFieldToMapping($field, $mapping);
        }
        $root->add($section, $mapping);
        $root
            ->add(sprintf('%s_all', $section), 'string')
            ->addLocalizedSubfields($this->locales)
            ->addRawVersion()
        ;
    }

    private function addFieldToMapping(Field $field, Mapping $mapping)
    {
        $type = $field->getType();
        $mapping->add($field->getName(), $type);

        if ($type === Mapping::TYPE_DATE) {
            $mapping->format(Mapping::DATE_FORMAT_CAPTION);
        }

        if ($type === Mapping::TYPE_STRING) {
            $searchable = $field->isSearchable();
            $facet = $field->isFacet();
            if (!$searchable && !$facet) {
                $mapping->notIndexed();
            } else {
                $mapping->addRawVersion();
                $mapping->addAnalyzedVersion($this->locales);
                $mapping->enableTermVectors(true);
            }
        }
    }

    private function getThesaurusPathMapping()
    {
        $mapping = new Mapping();
        foreach ($this->structure->getThesaurusEnabledFields() as $name => $_) {
            $mapping
                ->add($name, 'string')
                ->analyzer('thesaurus_path', 'indexing')
                ->analyzer('keyword', 'searching')
                ->addRawVersion()
            ;
        }

        return $mapping;
    }

    private function getMetadataTagMapping()
    {
        $mapping = new Mapping();
        foreach ($this->structure->getMetadataTags() as $tag) {
            $type = $tag->getType();
            $mapping->add($tag->getName(), $type);
            if ($type === Mapping::TYPE_STRING) {
                if ($tag->isAnalyzable()) {
                    $mapping->addRawVersion();
                } else {
                    $mapping->notAnalyzed();
                }
            }
        }

        return $mapping;
    }

    private function getFlagsMapping()
    {
        $mapping = new Mapping();
        foreach ($this->structure->getAllFlags() as $name => $_) {
            $mapping->add($name, 'boolean');
        }

        return $mapping;
    }
}
