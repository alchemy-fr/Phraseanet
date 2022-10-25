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
use Alchemy\Phrasea\SearchEngine\Elastic\DataboxFetcherFactory;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\RecordListFetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\ScheduledFetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Fetcher;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use databox;
use Iterator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use record_adapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RecordIndexer
{
    const TYPE_NAME = 'record';

    /**
     * @var RecordHelper
     */
    private $helper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var DataboxFetcherFactory
     */
    private $fetcherFactory;

    /**
     * @param DataboxFetcherFactory $fetcherFactory
     * @param RecordHelper $helper
     * @param LoggerInterface $logger
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        DataboxFetcherFactory $fetcherFactory,
        RecordHelper $helper,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->fetcherFactory = $fetcherFactory;
        $this->helper = $helper;
        $this->logger = $logger ?: new NullLogger();
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
     * @param databox $databox
     */
    public function populateIndex(BulkOperation $bulk, databox $databox)
    {
        $this->logger->info(sprintf('Indexing database %s...', $databox->get_viewname()));

        $submitted_records = [];
        // No delegate, scan all records
        $fetcher = $this->fetcherFactory->createFetcher($databox);

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

    /**
     * Index the records flagged as "to_index" on databox
     * called by task "indexer"
     *
     * @param BulkOperation $bulk
     * @param databox $databox
     */
    public function indexScheduled(BulkOperation $bulk, databox $databox)
    {
        $submitted_records = [];

        // Make fetcher
        $delegate = new ScheduledFetcherDelegate();
        $fetcher = $this->fetcherFactory->createFetcher($databox, $delegate);

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
            $submitted_records = [];
            $databox = $fetcher->getDatabox();

            // post fetch : flag records as "indexing"
            $fetcher->setPostFetch(function(array $records) use ($fetcher, $databox) {
                RecordQueuer::didStartIndexingRecords($records, $databox);
                // do not restart the fetcher since it has no clause on jetons
            });

            // bulk flush : flag records as "indexed"
            $bulk->onFlush(function($operation_identifiers) use ($databox, &$submitted_records) {
                $this->onBulkFlush($databox, $operation_identifiers, $submitted_records);
            });

            // Perform indexing
            $this->indexFromFetcher($bulk, $fetcher, $submitted_records);
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

            $fetchers[] = $this->fetcherFactory->createFetcher($databox, $delegate);
        }

        return $fetchers;
    }

    private function groupRecordsByDatabox(Iterator $records)
    {
        $databoxes = array();

        foreach ($records as $record) {
            /** @var record_adapter $record */
            $databox = $record->getDatabox();
            $k = $databox->get_sbas_id();
            if(!array_key_exists($k, $databoxes)) {
                $databoxes[$k] = [
                    'databox' => $databox,
                    'records' => []
                ];
            }
            $databoxes[$k]['records'][] = $record;
        }

        return array_values($databoxes);
    }

    private function indexFromFetcher(BulkOperation $bulk, Fetcher $fetcher, array &$submitted_records)
    {
        $databox = $fetcher->getDatabox();

        $sql = "SELECT prop FROM pref WHERE prop IN('thesaurus','thesaurus_index')"
            . " ORDER BY updated_on DESC, IF(prop='thesaurus', 'a', 'z') DESC LIMIT 1";

        if ($databox->get_connection()->fetchColumn($sql) == 'thesaurus') {
            // The thesaurus was modified, enforce index
            $this->eventDispatcher->dispatch(
                ThesaurusEvents::REINDEX_REQUIRED,
                new ReindexRequiredEvent($databox)
            );
        }

//        $first = true;

        /** @var record_adapter $record */
        while ($record = $fetcher->fetch()) {
//            if ($first) {
//                $sql = "SELECT prop FROM pref WHERE prop IN('thesaurus','thesaurus_index')"
//                    . " ORDER BY updated_on DESC, IF(prop='thesaurus', 'a', 'z') DESC LIMIT 1";
//
//                if ($databox->get_connection()->fetchColumn($sql) == 'thesaurus') {
//                    // The thesaurus was modified, enforce index
//                    $this->eventDispatcher->dispatch(
//                        ThesaurusEvents::REINDEX_REQUIRED,
//                        new ReindexRequiredEvent($databox)
//                    );
//                }
//
//                $first = false;
//            }

            $op_identifier = $this->getUniqueOperationId($record['id']);

            $this->logger->debug(sprintf("Indexing record %s of databox %s", $record['record_id'], $databox->get_sbas_id()));

            $params = array();
            $params['id'] = $record['id'];
            unset($record['id']);
            $params['type'] = self::TYPE_NAME;
            $params['body'] = $record;

            $submitted_records[$op_identifier] = $record;

            $bulk->index($params, $op_identifier);
        }
    }
}
