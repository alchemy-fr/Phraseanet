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
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordQueuer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use Closure;
use collection;
use databox;
use Elasticsearch\Client;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SplObjectStorage;
use Symfony\Component\Stopwatch\Stopwatch;

class Indexer
{
    const THESAURUS = 1;
    const RECORDS   = 2;

    /**
     * @var Client
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

    /**
     * @return Index
     */
    public function getIndex()
    {
        return $this->index;
    }

    public function createIndex($indexName = null)
    {
        $aliasName = $this->index->getName();
        if($indexName === null) {
            $indexName = $aliasName;
        }

        $now = sprintf("%s.%06d", Date('YmdHis'), 1000000*explode(' ', microtime())[0]) ;
        $recordIndexName =  $indexName . ('.r_' . $now);
        $termIndexName =  $indexName . ('.t_' . $now);

        $this->client->indices()->create([
            'index' => $recordIndexName,
            'body'  => [
                'settings' => [
                    'number_of_shards'   => $this->index->getOptions()->getShards(),
                    'number_of_replicas' => $this->index->getOptions()->getReplicas(),
                    'index.mapping.total_fields.limit' => $this->index->getOptions()->getTotalFieldsLimit(),
                    'max_result_window'  => $this->index->getOptions()->getMaxResultWindow(),
                    'analysis'           => $this->index->getAnalysis()
                ],
                'mappings' => [
                    RecordIndexer::TYPE_NAME => $this->index->getRecordIndex()->getMapping()->export()
                ]
            ]
        ]);

        $this->client->indices()->create([
            'index' => $termIndexName,
            'body'  => [
                'settings' => [
                    'number_of_shards'   => $this->index->getOptions()->getShards(),
                    'number_of_replicas' => $this->index->getOptions()->getReplicas(),
                    'analysis'           => $this->index->getAnalysis()
                ],
                'mappings' => [
                    TermIndexer::TYPE_NAME   => $this->index->getTermIndex()->getMapping()->export()
                ]
            ]
        ]);

        $this->client->indices()->updateAliases([
            'body' => [
                'actions' => [
                    [
                        // alias 1->1 to access the "record" index whithout knowing the date part
                        'add' => [
                            'indices' => [ $recordIndexName ],
                            'alias' => $aliasName . '.r'
                        ],
                    ],
                    [
                        // alias 1->1 to access the "term" index whithout knowing the date part
                        'add' => [
                            'indices' => [ $termIndexName ],
                            'alias' => $aliasName . '.t'
                        ],
                    ],
                    // alias 1->2 to access the whole index
                    [
                        'add' => [
                            'indices' => [ $recordIndexName, $termIndexName ],
                            'alias' => $aliasName
                        ],
                    ],
                ]
            ]
        ]);

        return [
            'index' => $indexName,
            'alias' => $aliasName,
            'date'  => $now
        ];    }

    public function updateMapping()
    {
//        $params = array();
//        $params['index'] = $this->index->getName();
//        $params['type'] = RecordIndexer::TYPE_NAME;
//        $params['body'][RecordIndexer::TYPE_NAME] = $this->index->getRecordIndex()->getMapping()->export();
//        $params['body'][TermIndexer::TYPE_NAME]   = $this->index->getTermIndex()->getMapping()->export();

        $params = [
            'index' => $this->index->getName() . '.t',      // here we can use the 1->1 alias
            'type' => TermIndexer::TYPE_NAME,
            'body' => [
                TermIndexer::TYPE_NAME => $this->index->getTermIndex()->getMapping()->export()
            ]
        ];

        // @todo This must throw a new indexation if a mapping is edited
        $this->client->indices()->putMapping($params);

        $params = [
            'index' => $this->index->getName() . '.r',      // here we can use the 1->1 alias
            'type' => RecordIndexer::TYPE_NAME,
            'body' => [
                RecordIndexer::TYPE_NAME => $this->index->getRecordIndex()->getMapping()->export()
            ]
        ];

        // @todo This must throw a new indexation if a mapping is edited
        $this->client->indices()->putMapping($params);
    }

    public function deleteIndex()
    {
        // we must delete indexes, we can't delete by alias
        //
        $indices = array_keys(
            $this->client->indices()->getAliases([
                    'index' => $this->index->getName()
                ]
            ));
        // we can delete the 2 indices (record, term) at once
        $this->client->indices()->delete([
            'index' => join(',', $indices)
        ]);
    }

    public function indexExists()
    {
        return $this->client->indices()->exists([
            'index' => $this->index->getName()
        ]);
    }

    /**
     * @param string $newIndexName
     * @param string $newAliasName
     * @return array
     */
    public function replaceIndex($newIndexName, $newAliasName, $newDate)
    {
        $ret = [];

        $oldIndexes = $this->client->indices()->getAlias(
            [
                'index' => $this->index->getName()
            ]
        );

        $newIndexes = $this->client->indices()->getAlias(
            [
                'index' => $newAliasName
            ]
        );

        $params = [
            'body' => [
                'actions' => []
            ]
        ];

        // delete old aliases and temp aliases
        foreach([$oldIndexes, $newIndexes] as $index) {
            foreach ($index as $indexName => $data) {
                foreach ($data['aliases'] as $aliasName => $data2) {
                    $params['body']['actions'][] = [
                        'remove' => [
                            'alias' => $aliasName,
                            'index' => $indexName,
                        ]
                    ];
                    $ret[] = [
                        'action' => "ALIAS_REMOVE",
                        'msg'    => sprintf('alias "%s" -> "%s" removed', $aliasName, $indexName),
                        'alias'  => $aliasName,
                        'index'  => $indexName,
                    ];
                }
            }
        }

        // create new aliases for direct acces to term and record
        $indices = [];
        foreach(['.r', '.t'] as $sfx) {
            $indices[] = ($indexName = $newIndexName . $sfx . '_' . $newDate);
            $params['body']['actions'][] = [
                'add' => [
                    'alias' => ($aliasName = $newIndexName . $sfx),
                    'index' => $indexName,
                ]
            ];
            $ret[] = [
                'action' => "ALIAS_ADD",
                'msg'    => sprintf('alias "%s" -> "%s" added', $aliasName, $indexName),
                'alias'  => $aliasName,
                'index'  => $indexName,
            ];
        }
        // create top-level alias
        $params['body']['actions'][] = [
            'add' => [
                'alias'   => $newIndexName,
                'indices' => $indices,
            ]
        ];
        $ret[] = [
            'action'  => "ALIAS_ADD",
            'msg'     => sprintf('alias "%s" -> ["%s"] added', $newIndexName, join('", "', $indices)),
            'alias'   => $newIndexName,
            'indices' => $indices,
        ];

        $this->client->indices()->updateAliases($params);

        // delete old index(es)
        $params = [
            'index' => []
        ];
        foreach($oldIndexes as $oldIndexName => $data) {
            $params['index'][] = $oldIndexName;
            $ret[] = [
                'action' => "INDEX_DELETE",
                'msg'    => sprintf('index "%s" deleted', $oldIndexName),
                'index'  => $oldIndexName,
            ];
        }
        $this->client->indices()->delete(
            $params
        );

        return $ret;
    }

    public function populateIndex($what, databox $databox)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('populate');

        if ($what & self::THESAURUS) {
            $this->apply(
                function (BulkOperation $bulk, $index) use ($databox) {
                    $bulk->setDefaultIndex($index->getName() . '.t');
                    $this->termIndexer->populateIndex($bulk, $databox);

                    // Record indexing depends on indexed terms so we need to make
                    // everything ready to search
                    $bulk->flush();
                    $this->client->indices()->refresh();
                },
                $this->index
            );
        }

        if ($what & self::RECORDS) {
            $this->apply(
                function (BulkOperation $bulk, $index) use ($databox) {
                    $bulk->setDefaultIndex($index->getName() . '.r');
                    $databox->clearCandidates();
                    $this->recordIndexer->populateIndex($bulk, $databox);

                    // Final flush
                    $bulk->flush();
                },
                $this->index
            );
        }

        /*
        $this->apply(
            function (BulkOperation $bulk) use ($what, $databox) {
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
            },
            $this->index
        );
        */

        // Optimize index
        $this->client->indices()->forceMerge(
            [
                'index' => $this->index->getName()
            ]
        );
//        $this->client->indices()->optimize(
//            [
//                'index' => $this->index->getName()
//            ]
//        );

        $event = $stopwatch->stop('populate');

        return [
            'duration' => $event->getDuration(),
            'memory'   => $event->getMemory()
        ];
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

    public function scheduleRecordsFromDataboxForIndexing(databox $databox)
    {
        RecordQueuer::queueRecordsFromDatabox($databox);
    }

    public function scheduleRecordsFromCollectionForIndexing(collection $collection)
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
     * @param databox $databox    databox to index
     * @throws Exception
     */
    public function indexScheduledRecords(databox $databox)
    {
        $this->apply(
            function(BulkOperation $bulk) use($databox) {
                $bulk->setDefaultIndex($this->getIndex()->getName() . '.r');

                $this->recordIndexer->indexScheduled($bulk, $databox);
            },
            $this->index
        );
    }

    public function flushQueue()
    {
        // Do not reindex records modified then deleted in the request
        $this->indexQueue->removeAll($this->deleteQueue);

        // Skip if nothing to do
        if (count($this->indexQueue) === 0 && count($this->deleteQueue) === 0) {
            return;
        }

        $this->apply(
            function(BulkOperation $bulk) {
                $bulk->setDefaultIndex($this->getIndex()->getName() . '.r');

                $this->recordIndexer->index($bulk, $this->indexQueue);
                $this->recordIndexer->delete($bulk, $this->deleteQueue);
                $bulk->flush();
            },
            $this->index
        );

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

    public function getSettings(array $params)
    {
        try {
            //Get setting from index
            return $this->client->indices()->getSettings($params);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
