<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\BulkOperation;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordQueuer;
use appbox;
use Closure;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use igorw;
use Psr\Log\NullLogger;
use record_adapter;
use Symfony\Component\Stopwatch\Stopwatch;
use SplObjectStorage;

class Indexer
{
    const THESAURUS = 1;
    const RECORDS   = 2;

    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var SplObjectStorage|RecordInterface[]
     */
    private $indexQueue;

    /**
     * @var SplObjectStorage|RecordInterface[]
     */
    private $deleteQueue;

    /**
     * @var RecordIndexer
     */
    private $recordIndexer;

    /**
     * @var TermIndexer
     */
    private $termIndexer;

    /**
     * @var Index
     */
    private $index;

    public function __construct(
        Client $client,
        Index $index,
        TermIndexer $termIndexer,
        RecordIndexer $recordIndexer,
        LoggerInterface $logger = null
    )
    {
        $this->client = $client;
        $this->index = $index;
        $this->recordIndexer = $recordIndexer;
        $this->termIndexer = $termIndexer;
        $this->logger = $logger ?: new NullLogger();

        $this->indexQueue = new SplObjectStorage();
        $this->deleteQueue = new SplObjectStorage();
    }

    public function createIndex($withMapping = true)
    {
        $params = array();
        $params['index'] = $this->index->getName();
        $params['body']['settings']['number_of_shards'] = $this->index->getOptions()->getShards();
        $params['body']['settings']['number_of_replicas'] = $this->index->getOptions()->getReplicas();
        $params['body']['settings']['analysis'] = $this->index->getAnalysis();

        if ($withMapping) {
            $params['body']['mappings'][RecordIndexer::TYPE_NAME] = $this->index->getRecordIndex()->getMapping()->export();
            $params['body']['mappings'][TermIndexer::TYPE_NAME]   = $this->index->getTermIndex()->getMapping()->export();
        }

        $this->client->indices()->create($params);
    }

    public function updateMapping()
    {
        $params = array();
        $params['index'] = $this->index->getName();
        $params['type'] = RecordIndexer::TYPE_NAME;
        $params['body'][RecordIndexer::TYPE_NAME] = $this->index->getRecordIndex()->getMapping()->export();
        $params['body'][TermIndexer::TYPE_NAME]   = $this->index->getTermIndex()->getMapping()->export();

        // @todo This must throw a new indexation if a mapping is edited
        $this->client->indices()->putMapping($params);
    }

    public function deleteIndex()
    {
        $this->client->indices()->delete([
            'index' => $this->index->getName()
        ]);
    }

    public function indexExists()
    {
        return $this->client->indices()->exists([
            'index' => $this->index->getName()
        ]);
    }

    public function populateIndex($what, \databox $databox)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('populate');

        $this->apply(function (BulkOperation $bulk) use ($what, $databox) {
            if ($what & self::THESAURUS) {
                $this->termIndexer->populateIndex($bulk, $databox);

                // Record indexing depends on indexed terms so we need to make
                // everything ready to search
                $bulk->flush();
                $this->client->indices()->refresh();
            }

            if ($what & self::RECORDS) {
                $databox->clearCandidates();
                $this->recordIndexer->populateIndex($bulk, $databox);

                // Final flush
                $bulk->flush();
            }
        }, $this->index);

        // Optimize index
        $params = array('index' => $this->index->getName());
        $this->client->indices()->optimize($params);

        $event = $stopwatch->stop('populate');
        printf("Indexation finished in %s min (Mem. %s Mo)", ($event->getDuration()/1000/60), bcdiv($event->getMemory(), 1048576, 2));
    }

    public function migrateMappingForDatabox($databox)
    {
        // TODO Migrate mapping
        // - Create a new index
        // - Dump records using scroll API
        // - Insert them in created index (except those in the changed databox)
        // - Reindex databox's records from DB
        // - Make alias point to new index
        // - Delete old index

        // $this->updateMapping();
        // RecordQueuer::queueRecordsFromDatabox($databox);
    }

    public function scheduleRecordsFromDataboxForIndexing(\databox $databox)
    {
        RecordQueuer::queueRecordsFromDatabox($databox);
    }

    public function scheduleRecordsFromCollectionForIndexing(\collection $collection)
    {
        RecordQueuer::queueRecordsFromCollection($collection);
    }

    public function indexRecord(RecordInterface $record)
    {
        $this->indexQueue->attach($record);
    }

    public function deleteRecord(RecordInterface $record)
    {
        $this->deleteQueue->attach($record);
    }

    /**
     * @param \databox $databox    databox to index
     * @throws \Exception
     */
    public function indexScheduledRecords(\databox $databox)
    {
        $this->apply(function(BulkOperation $bulk) use($databox) {
            $this->recordIndexer->indexScheduled($bulk, $databox);
        }, $this->index);
    }

    public function flushQueue()
    {
        // Do not reindex records modified then deleted in the request
        $this->indexQueue->removeAll($this->deleteQueue);

        // Skip if nothing to do
        if (count($this->indexQueue) === 0 && count($this->deleteQueue) === 0) {
            return;
        }

        $this->apply(function(BulkOperation $bulk) {
            $this->recordIndexer->index($bulk, $this->indexQueue);
            $this->recordIndexer->delete($bulk, $this->deleteQueue);
            $bulk->flush();
        }, $this->index);

        $this->indexQueue = new SplObjectStorage();
        $this->deleteQueue = new SplObjectStorage();
    }

    private function apply(Closure $work, Index $index)
    {
        // Prepare the bulk operation
        $bulk = new BulkOperation($this->client, $this->logger);
        $bulk->setDefaultIndex($index->getName());
        $bulk->setAutoFlushLimit(1000);

        // Do the work
        $work($bulk, $index);

        // Flush just in case, it's a noop when already done
        $bulk->flush();
    }
}
