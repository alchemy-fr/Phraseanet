<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
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
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\MappingBuilder;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\CandidateTerms;
use databox;
use Iterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\CssSelector\XPath\Extension\FunctionExtension;

class RecordIndexer
{
    const TYPE_NAME = 'record';

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var RecordHelper
     */
    private $helper;

    /**
     * @var Thesaurus
     */
    private $thesaurus;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Structure $structure
     * @param RecordHelper $helper
     * @param Thesaurus $thesaurus
     * @param array $locales
     * @param LoggerInterface $logger
     */
    public function __construct(Structure $structure, RecordHelper $helper, Thesaurus $thesaurus, array $locales, LoggerInterface $logger)
    {
        $this->structure = $structure;
        $this->helper = $helper;
        $this->thesaurus = $thesaurus;
        $this->locales = $locales;
        $this->logger = $logger;
    }

    /**
     * @param $record_key
     * @return string
     */
    private function getUniqueOperationId($record_key)
    {
        $_key = dechex(mt_rand());
        return $_key . '_' . $record_key;
    }

    /**
     * ES made a bulk op, check our (index) operations to drop the "indexing" & "to_index" jetons
     *
     * @param databox $databox
     * @param array $operation_identifiers  key:op_identifier ; value:operation result (json from es)
     * @param array $submitted_records       records indexed, key:op_identifier
     */
    private function onBulkFlush(databox $databox, array $operation_identifiers, array &$submitted_records)
    {
        // nb: because the same bulk could be used by many "clients", this (each) callback may receive
        // operation_identifiers that does not belong to it.
        // flag only records that the fetcher worked on
        $records = array_intersect_key(
            $submitted_records,        // this is OUR records list
            $operation_identifiers          // reduce to the records indexed by this bulk (should be the same...)
        );

        if (count($records) === 0) {
            return;
        }

        // Commit and remove "indexing" flag
        RecordQueuer::didFinishIndexingRecords(array_values($records), $databox);

        foreach (array_keys($records) as $id) {
            unset($submitted_records[$id]);
        }
    }

    /**
     * index whole databox(es), don't test actual "jetons"
     * called by command "populate"
     *
     * @param BulkOperation $bulk
     * @param databox[] $databoxes
     */
    public function populateIndex(BulkOperation $bulk, array $databoxes)
    {
        foreach ($databoxes as $databox) {
            $this->logger->info(sprintf('Indexing database %s...', $databox->get_viewname()));

            $submitted_records = [];
            $fetcher = $this->createFetcherForDatabox($databox);    // no delegate, scan the whole records

            // post fetch : flag records as "indexing"
            $fetcher->setPostFetch(function(array $records) use ($databox, $fetcher) {
                RecordQueuer::didStartIndexingRecords($records, $databox);
                // do not restart the fetcher since it has no clause on jetons
            });

            // bulk flush : flag records as "indexed"
            $bulk->onFlush(function($operation_identifiers) use ($databox, &$submitted_records) {
                $this->onBulkFlush($databox, $operation_identifiers, $submitted_records);
            });

            // Perform indexing
            $this->indexFromFetcher($bulk, $fetcher, $submitted_records);

            $this->logger->info(sprintf('Finished indexing %s', $databox->get_viewname()));
        }
    }

    /**
     * Index the records flagged as "to_index" on databoxes
     * called by task "indexer"
     *
     * @param BulkOperation $bulk
     * @param databox[] $databoxes
     */
    public function indexScheduled(BulkOperation $bulk, array $databoxes)
    {
        foreach ($databoxes as $databox) {
            $this->indexScheduledInDatabox($bulk, $databox);
        }
    }

    private function indexScheduledInDatabox(BulkOperation $bulk, databox $databox)
    {
        $submitted_records = [];

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
        $bulk->onFlush(function($operation_identifiers) use ($databox, &$submitted_records) {
            $this->onBulkFlush($databox, $operation_identifiers, $submitted_records);
        });

        // Perform indexing
        $this->indexFromFetcher($bulk, $fetcher, $submitted_records);
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
            $bulk->delete([
                'id' => $record->getId(),
                'type' => self::TYPE_NAME
            ], null);
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

    private function indexFromFetcher(BulkOperation $bulk, Fetcher $fetcher, array &$submitted_records)
    {
        /** @var RecordInterface $record */
        while ($record = $fetcher->fetch()) {
            $op_identifier = $this->getUniqueOperationId($record['id']);

            $params = array();
            $params['id'] = $record['id'];
            unset($record['id']);
            $params['type'] = self::TYPE_NAME;
            $params['body'] = $record;

            $submitted_records[$op_identifier] = $record;

            $bulk->index($params, $op_identifier);
        }
    }


    public function getMapping()
    {
        $mapping = new MappingBuilder();

        // Compound primary key
        $mapping->addField('record_id', FieldMapping::TYPE_INTEGER);
        $mapping->addField('databox_id', FieldMapping::TYPE_INTEGER);

        // Database name (still indexed for facets)
        $mapping->addStringField('databox_name')->disableAnalysis();
        // Unique collection ID
        $mapping->addField('base_id', FieldMapping::TYPE_INTEGER);
        // Useless collection ID (local to databox)
        $mapping->addField('collection_id', FieldMapping::TYPE_INTEGER)->disableIndexing();
        // Collection name (still indexed for facets)
        $mapping->addStringField('collection_name')->disableAnalysis();

        $mapping->addStringField('uuid')->disableIndexing();
        $mapping->addStringField('sha256')->disableIndexing();
        $mapping->addStringField('original_name')->disableIndexing();
        $mapping->addStringField('mime')->disableAnalysis();
        $mapping->addStringField('type')->disableAnalysis();
        $mapping->addStringField('record_type')->disableAnalysis();

        $mapping->addDateField('created_on', FieldMapping::DATE_FORMAT_MYSQL_OR_CAPTION);
        $mapping->addDateField('updated_on', FieldMapping::DATE_FORMAT_MYSQL_OR_CAPTION);

        $mapping->add($this->buildThesaurusPathMapping('concept_path'));
        $mapping->add($this->buildMetadataTagMapping('metadata_tags'));
        $mapping->add($this->buildFlagMapping('flags'));

        $mapping->addField('flags_bitfield', FieldMapping::TYPE_INTEGER)->disableIndexing();
        $mapping->addField('subdefs', FieldMapping::TYPE_OBJECT)->disableMapping();
        $mapping->addField('title', FieldMapping::TYPE_OBJECT)->disableMapping();

        // Caption mapping
        $this->buildCaptionMapping($mapping, 'caption', $this->structure->getUnrestrictedFields());
        $this->buildCaptionMapping($mapping, 'private_caption', $this->structure->getPrivateFields());

        echo var_export($mapping->getMapping()->export()); die();
    }

    private function buildCaptionMapping(MappingBuilder $parent, $name, array $fields)
    {
        $fieldConverter = new Mapping\FieldToFieldMappingConverter();
        $captionMapping = new Mapping\ComplexFieldMapping($name, FieldMapping::TYPE_OBJECT);

        $captionMapping->useAsPropertyContainer();

        foreach ($fields as $field) {
            $captionMapping->addChild($fieldConverter->convertField($field, $this->locales));
        }

        $parent->add($captionMapping);

        $localizedCaptionMapping = new Mapping\StringFieldMapping(sprintf('%s_all', $name));
        $localizedCaptionMapping
            ->addLocalizedChildren($this->locales)
            ->addChild((new Mapping\StringFieldMapping('raw'))->enableRawIndexing());

        $parent->add($localizedCaptionMapping);

        return $captionMapping;
    }

    private function buildThesaurusPathMapping($name)
    {
        $thesaurusMapping = new Mapping\ComplexFieldMapping($name, FieldMapping::TYPE_OBJECT);

        foreach (array_keys($this->structure->getThesaurusEnabledFields()) as $name) {
            $child = new Mapping\StringFieldMapping($name);

            $child->setAnalyzer('thesaurus_path', 'indexing');
            $child->setAnalyzer('keyword', 'searching');
            $child->addChild((new Mapping\StringFieldMapping('raw'))->enableRawIndexing());

            $thesaurusMapping->addChild($thesaurusMapping);
        }

        return $thesaurusMapping;
    }

    private function buildMetadataTagMapping($name)
    {
        $tagConverter = new Mapping\MetadataTagToFieldMappingConverter();
        $metadataMapping = new Mapping\ComplexFieldMapping($name, FieldMapping::TYPE_OBJECT);

        $metadataMapping->useAsPropertyContainer();

        foreach ($this->structure->getMetadataTags() as $tag) {
            $metadataMapping->addChild($tagConverter->convertTag($tag));
        }

        return $metadataMapping;
    }

    private function buildFlagMapping($name)
    {
        $index = 0;
        $flagMapping = new Mapping\ComplexFieldMapping($name, FieldMapping::TYPE_OBJECT);

        $flagMapping->useAsPropertyContainer();

        foreach ($this->structure->getAllFlags() as $childName => $_) {
            if (trim($childName) == '') {
                $childName = 'flag_' . $index++;
            }

            $flagMapping->addChild(new FieldMapping($childName, FieldMapping::TYPE_BOOLEAN));
        }

        return $flagMapping;
    }
}
