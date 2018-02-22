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

use Alchemy\Phrasea\Core\Event\Thesaurus\ReindexRequiredEvent;
use Alchemy\Phrasea\Core\Event\Thesaurus\ThesaurusEvents;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegateInterface;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\RecordIdListFetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\ScheduledFetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Fetcher;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\CoreHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\FlagHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\MetadataHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\SubDefinitionHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\ThesaurusHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\TitleHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\CandidateTerms;
use databox;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use record_adapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


class RecordIndexer
{
    const TYPE_NAME = 'record';

    /** @var  RecordIndexer */
    private $indexer;

    /** @var  ElasticsearchOptions */
    private $options;

    /** @var  Client $client */
    private $client;

    /**
     * @var RecordHelper
     */
    private $recordHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param Indexer $indexer
     * @param RecordHelper $recordHelper
     * @param LoggerInterface $logger
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(Indexer $indexer,
                                RecordHelper $recordHelper,
                                EventDispatcherInterface $eventDispatcher, LoggerInterface $logger = null)
    {
        $this->indexer = $indexer;
        $this->recordHelper = $recordHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger ?: new NullLogger();

        $this->options = $indexer->getIndex()->getOptions();
        $this->client = $indexer->getClient();
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
     * @param databox $databox
     */
    public function populateIndex(databox $databox)
    {
        $this->logger->info(sprintf('Indexing database %s...', $databox->get_dbname()));

        $indexName = $this->indexer->getDataboxIndexBasename($databox) . '.r';

        // Prepare the bulk operation
        $bulk = new BulkOperation($this->client, $indexName, $this->logger);
        $bulk->setAutoFlushLimit(15);

        $submitted_records = [];
        // No delegate, scan all records
        $fetcher = $this->createFetcherForDatabox($databox);

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

        // Flush just in case, it's a noop when already done
        $bulk->flush();

        $this->logger->info(sprintf('Finished indexing %s', $databox->get_dbname()));
    }

    /**
     * Index the records flagged as "to_index" on databox
     * called by task "indexer"
     *
     * @param databox $databox
     */
    public function indexScheduled(databox $databox)
    {
        $submitted_records = [];

        $indexName = $this->indexer->getDataboxIndexBasename($databox) . '.r';

        // Prepare the bulk operation
        $bulk = new BulkOperation($this->client, $indexName, $this->logger);
        $bulk->setAutoFlushLimit(15);

        // Make fetcher
        $delegate = new ScheduledFetcherDelegate();
        $fetcher = $this->createFetcherForDatabox($databox, $delegate);

        // post fetch : flag records as "indexing"
        $fetcher->setPostFetch(function(array $records) use ($databox, $fetcher) {
            $this->logger->debug(sprintf("indexing %d records", count($records)));
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

        $bulk->flush();
    }

    /**
     * Index a list of records
     *
     * @param Databox $databox,
     * @param int[] $recordIds
     */
    public function index(Databox $databox, array $recordIds)
    {
        $submitted_records = [];

        $indexName = $this->indexer->getDataboxIndexBasename($databox) . '.r';

        // Prepare the bulk operation
        $bulk = new BulkOperation($this->client, $indexName, $this->logger);
        $bulk->setAutoFlushLimit(15);

        // Make fetcher
        $delegate = new RecordIdListFetcherDelegate($recordIds);
        $fetcher = $this->createFetcherForDatabox($databox, $delegate);

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

        $bulk->flush();
    }

    /**
     * Deleta a list of records
     *
     * @param databox $databox
     * @param int[] $recordIds
     */
    public function delete(databox $databox, $recordIds)
    {
        $indexName = $this->indexer->getDataboxIndexBasename($databox) . '.r';

        // Prepare the bulk operation
        $bulk = new BulkOperation($this->client, $indexName, $this->logger);
        $bulk->setAutoFlushLimit(15);

        foreach ($recordIds as $record_id) {
            $params = array();
            $params['id'] = $record_id;
            $params['type'] = self::TYPE_NAME;
            $bulk->delete($params, null);       // no operationIdentifier is related to a delete op
        }

        $bulk->flush();
    }

    private function createFetcherForDatabox(databox $databox, FetcherDelegateInterface $fetcherDelegate = null)
    {
        $connection = $databox->get_connection();

        // a thesaurus linked to the .t index for this databox
        $thesaurusOptions = clone $this->options;

        //$thesaurusOptions->setIndexName($this->getTermIndexName());
        $termIndexName = $this->indexer->getDataboxIndexBasename($databox) . '.t';
        $thesaurusOptions->setIndexName($termIndexName);

        $thesaurus = new Thesaurus($this->client, $thesaurusOptions, $this->logger);   // !!! specific options 'index'

        $structure = GlobalStructure::createFromDataboxes([$databox]);

        $candidateTerms = new CandidateTerms($databox);

        $fetcher = new Fetcher(
            $databox,
            $this->options,
            [
                new CoreHydrator($databox->get_sbas_id(), $databox->get_viewname(), $this->recordHelper),
                new TitleHydrator($connection, $this->recordHelper),
                new MetadataHydrator($connection, $structure, $this->recordHelper),
                new FlagHydrator($structure, $databox),
                new ThesaurusHydrator($structure, $thesaurus, $candidateTerms),
                new SubDefinitionHydrator($connection)
            ],
            $fetcherDelegate
        );

        $fetcher->setBatchSize(200);
        $fetcher->onDrain(function() use ($candidateTerms) {
            $candidateTerms->save();
        });

        return $fetcher;
    }

    private function indexFromFetcher(BulkOperation $bulk, Fetcher $fetcher, array &$submitted_records)
    {
        $databox = $fetcher->getDatabox();
        $first = true;

        /** @var record_adapter $record */
        while ($record = $fetcher->fetch()) {
            if ($first) {
                $sql = "SELECT prop FROM pref WHERE prop IN('thesaurus','thesaurus_index')"
                    . " ORDER BY updated_on DESC, IF(prop='thesaurus', 'a', 'z') DESC LIMIT 1";

                if ($databox->get_connection()->fetchColumn($sql) == 'thesaurus') {
                    // The thesaurus was modified, enforce index
                    $this->eventDispatcher->dispatch(
                        ThesaurusEvents::REINDEX_REQUIRED,
                        new ReindexRequiredEvent($databox)
                    );
                }

                $first = false;
            }

            $op_identifier = $this->getUniqueOperationId($record['id']);

            $this->logger->debug(sprintf("Indexing record %s of databox %s", $record['record_id'], $databox->get_sbas_id()));

            $id = $record['id'];
            unset($record['id']);

            $params = [
                'id'   => $id,
                'type' => self::TYPE_NAME,
                'body' => $record
            ];

            $submitted_records[$op_identifier] = $record;

            $bulk->index($params, $op_identifier);
        }
    }
}
