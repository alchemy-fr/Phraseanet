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
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordQueuer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use appbox;
use databox;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use igorw;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;


class Indexer
{
    const THESAURUS = 1;
    const RECORDS = 2;

    /** @var  appbox */
    private $appbox;

    /** @var  ElasticsearchOptions */
    private $options;

    /** @var Client */
    private $client;

    /** @var  EventDispatcherInterface */
    private $dispatcher;

    /** @var RecordHelper */
    private $recordHelper;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var Index
     */
    private $index;

    /**
     * @var RecordIndexer
     */
    private $recordIndexer;

    /**
     * @var TermIndexer
     */
    private $termIndexer;

    // array of "tools" for each databox (queues, indexer, thesaurus, ...)
    private $databoxToolbox;


    public function __construct(appbox $appbox, ElasticsearchOptions $options, Client $client, Index $index,
                                // TermIndexer $termIndexer,
                                // RecordIndexer $recordIndexer,
                                EventDispatcherInterface $dispatcher, RecordHelper $recordHelper, LoggerInterface $logger = null)
    {
        $this->appbox = $appbox;
        $this->options = $options;
        $this->client = $client;
        $this->index = $index;
        // $this->recordIndexer = $recordIndexer;
        // $this->termIndexer = $termIndexer;
        $this->dispatcher = $dispatcher;
        $this->recordHelper = $recordHelper;
        $this->logger = $logger ?: new NullLogger();

        $this->databoxToolbox = [];

        $this->recordIndexer = new RecordIndexer(
            $this,
            $recordHelper,
            $dispatcher,
            $this->logger
        );

        $this->termIndexer = new TermIndexer(
            $this,
            $client,
            $this->logger
        );
    }

    public function __destruct()
    {
        $this->flushQueue();
    }

    /**
     * @return Index
     */
    public function getIndex()
    {
        return $this->index;
    }

    public function getClient()
    {
        return $this->client;
    }

    /*
    public function no_createIndexForDatabox(databox $dbox)
    {
        $indexSuffix = sprintf("%s.%06d", Date('YmdHis'), 1000000 * explode(' ', microtime())[0]);
        $indexName = $dbox->get_dbname() . '_' . $indexSuffix;

        $settings = [
            'number_of_shards'   => $this->index->getOptions()->getShards(),
            'number_of_replicas' => $this->index->getOptions()->getReplicas(),
            'analysis'           => $this->index->getAnalysis(),
        ];

        $actions = [];
        $mainIndex = $this->options->getIndexName();

        // create the "term" index
        $termIndexer = $this->getTermIndexerForDatabox($dbox);
        $termMapping = $this->index->getTermIndex()->getMapping()->export();
        $termIndexName = $termIndexer->createIndex($settings, $termMapping, $indexSuffix);

        // add it to "main.t" and "main" aliases
        $this->logger->info(sprintf("Adding index \"%s\" to aliases \"%s.t\" and \"%s\"", $termIndexName, $mainIndex, $mainIndex));
        $actions[] = [
            'add' => [
                'index' => $termIndexName,
                'alias' => $mainIndex . '.t',
            ]
        ];
        $actions[] = [
            'add' => [
                'index' => $termIndexName,
                'alias' => $mainIndex,
            ]
        ];

        // create the "record" index
        $recordIndexer = $this->getRecordIndexerForDatabox($dbox);
        $recordMapping = $this->index->getRecordIndex()->getMapping()->export();
        $recordIndexName = $recordIndexer->createIndex($settings, $recordMapping, $indexSuffix);

        // add it to "main.r" and "main" aliases
        $this->logger->info(sprintf("Adding index \"%s\" to aliases \"%s.r\" and \"%s\"", $recordIndexName, $mainIndex, $mainIndex));
        $actions[] = [
            'add' => [
                'index' => $recordIndexName,
                'alias' => $mainIndex . '.r',
            ]
        ];
        $actions[] = [
            'add' => [
                'index' => $recordIndexName,
                'alias' => $mainIndex,
            ]
        ];
        $params = [
            'body' => [
                'actions' => $actions
            ]
        ];

        $this->client->indices()->updateAliases($params);
    }
    */

    private function &getToolboxForDatabox($databox_id)
    {
        if (!array_key_exists($databox_id, $this->databoxToolbox)) {
            $this->databoxToolbox[$databox_id] = [];
        }

        return $this->databoxToolbox[$databox_id];
    }

    /**
     * @param $databox_id
     * @return array[]
     */
    private function &getQueuesForDatabox($databox_id)
    {
        $toolbox = &$this->getToolboxForDatabox($databox_id);
        if (!array_key_exists('queues', $toolbox)) {
            $toolbox['queues'] = [
                'index'  => [],     // array or record_ids as key (to keep unique), value is useless
                'delete' => [],     // '
            ];
        }

        return $toolbox['queues'];
    }

    /**
     * Create 2 real ES indexes (records=".r", terms=".t") for a databox, including a timestamp
     *
     * @param databox $databox
     * @return string   the basename including timestamp
     */
    public function createIndex(databox $databox)
    {
        $indexName = $databox->get_dbname();
        $now = sprintf("%s.%06d", Date('YmdHis'), 1000000 * explode(' ', microtime())[0]);
        $indexName .= '_' . $now;

        $params = [
            'index' => $indexName . '.r',
            'body'  => [
                'settings' => [
                    'number_of_shards'   => $this->index->getOptions()->getShards(),
                    'number_of_replicas' => $this->index->getOptions()->getReplicas(),
                    'analysis'           => $this->index->getAnalysis()
                ],
                'mappings' => [
                    // the mapping is different for each db
                    RecordIndexer::TYPE_NAME => $this->index->getRecordIndex($databox)->getMapping()->export(),
                ]
            ]
        ];

        $this->client->indices()->create($params);

        $params = [
            'index' => $indexName . '.t',
            'body'  => [
                'settings' => [
                    'number_of_shards'   => $this->index->getOptions()->getShards(),
                    'number_of_replicas' => $this->index->getOptions()->getReplicas(),
                    'analysis'           => $this->index->getAnalysis()
                ],
                'mappings' => [
                    // the mapping is fixed (not related to db structure)
                    TermIndexer::TYPE_NAME => $this->index->getTermIndex()->getMapping()->export()
                ]
            ]
        ];

        $this->client->indices()->create($params);

        return $indexName;
    }

    /**
     * attach index (really 2 indexes) to aliases)
     * the indexes will be detached from previous aliases
     *
     * @param string $indexBasename     basename for the 2 real indexes (add ".r" and ".t")
     * @param string $dbAliasname       alias as dbname to attach the 2 real indexes
     * @param string $appAliasname      alias as appname to attach only the .r index
     */
    public function createAliases($indexBasename, $dbAliasname, $appAliasname)
    {
        $actions = [];

        $indexes = $this->client->indices()->getAliases([]);
        foreach($indexes as $indexName => $data) {
            if( ($indexName === $indexBasename.'.r') || ($indexName === $indexBasename.'.t') ) {
                foreach($data['aliases'] as $aliasName => $data2) {
                    $actions[] = [
                        'remove' => [
                            'alias' => $aliasName,
                            'index' => $indexName,
                        ]
                    ];
                }
            }
        }

        // records ".r" and terms ".t" indexes for a db are grouped in a common "db"
        // this will allow to find the physical indexes related to a db
        $actions[] = [
            'add' => [
                'index' => $indexBasename . '.r',
                'alias' => $dbAliasname
            ]
        ];
        $actions[] = [
            'add' => [
                'index' => $indexBasename . '.t',
                'alias' => $dbAliasname
            ]
        ];


        // all records indexes are searchable in a common "app" (todo: add ".r" ?)
        $actions[] = [
            'add' => [
                'index' => $indexBasename . '.r',
                'alias' => $appAliasname
            ]
        ];

        // all thesaurus indexes are searchable in a common "app" . ".t"
        $actions[] = [
            'add' => [
                'index' => $indexBasename . '.t',
                'alias' => $appAliasname . '.t'
            ]
        ];
        //$actions[] = [
        //    'add' => [
        //        'index' => $indexBasename . '.t',
        //        'alias' => $appAliasname
        //    ]
        //];

        $params = [
            'body' => [
                'actions' => $actions
            ]
        ];

        $this->client->indices()->updateAliases($params);
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

    public function deleteIndex(databox $databox)
    {
        $indexes = $this->client->indices()->getAliases(['index' => $databox->get_dbname()]);

        foreach($indexes as $name=>$index) {
            $this->client->indices()->delete([
                'index' => $name
            ]);
        }
    }

    public function getDataboxIndexBasename(databox $databox)
    {
        $indexes = $this->client->indices()->getAliases(['index' => $databox->get_dbname()]);
        $a = array_keys($indexes)[0];

        return substr($a, 0, -2);
    }

    /**
     * @param databox $databox
     * @return bool
     *
     * @throws \Exception
     */
    public function indexExists(databox $databox)
    {
        try {
            $this->client->indices()->getAliases(['index' => $databox->get_dbname()]);
            return true;
        }
        catch(Missing404Exception $e) {
            return false;
        }
    }

    public function listIndexes()
    {
        $ret = $this->client->indices()->getAliases([]);

        return var_export($ret, true);
    }

    /**
     * @param string $newIndexName
     * @param string $newAliasName
     * @return array
     */
    public function replaceIndex($newIndexName, $newAliasName)
    {
        $ret = [];

        $oldIndexes = $this->client->indices()->getAlias(
            [
                'index' => $this->index->getName()
            ]
        );

        // delete old alias(es), only one alias on one index should exist
        foreach($oldIndexes as $oldIndexName => $data) {
            foreach($data['aliases'] as $oldAliasName => $data2) {
                $params['body']['actions'][] = [
                    'remove' => [
                        'alias' => $oldAliasName,
                        'index' => $oldIndexName,
                    ]
                ];
                $ret[] = [
                    'action' => "ALIAS_REMOVE",
                    'msg'    => sprintf('alias "%s" -> "%s" removed', $oldAliasName, $oldIndexName),
                    'alias'  => $oldAliasName,
                    'index'  => $oldIndexName,
                ];
            }
        }

        // create new alias
        $params['body']['actions'][] = [
            'add' => [
                'alias' => $this->index->getName(),
                'index' => $newIndexName,
            ]
        ];
        $ret[] = [
            'action' => "ALIAS_ADD",
            'msg'   => sprintf('alias "%s" -> "%s" added', $this->index->getName(), $newIndexName),
            'alias' => $this->index->getName(),
            'index' => $newIndexName,
        ];

        //
        $params['body']['actions'][] = [
            'remove' => [
                'alias' => $newAliasName,
                'index' => $newIndexName,
            ]
        ];
        $ret[] = [
            'action' => "ALIAS_REMOVE",
            'msg'    => sprintf('alias "%s" -> "%s" removed', $newAliasName, $newIndexName),
            'alias'  => $newAliasName,
            'index'  => $newIndexName,
        ];


        $this->client->indices()->updateAliases($params);

        // delete old index(es), only one index should exist
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

    public function populateIndex(databox $databox, $what)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('populate');

        if ($what & self::THESAURUS) {
            $this->termIndexer->populateIndex($databox);
        }
        $this->client->indices()->refresh();
        $this->client->indices()->clearCache();
        $this->client->indices()->flushSynced();

        // Do the work
        if ($what & self::RECORDS) {
            $databox->clearCandidates();
            $this->recordIndexer->populateIndex($databox);
        }

        // Optimize index
        //$params = array('index' => $this->index->getName());
        //$this->client->indices()->optimize($params);

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
        $q = &$this->getQueuesForDatabox($record->getDataboxId());
        $q['index'][$record->getRecordId()] = true;     // key prevents doubles, value is useless
    }

    public function deleteRecord(RecordInterface $record)
    {
        $q = &$this->getQueuesForDatabox($record->getDataboxId());
        $q['delete'][$record->getRecordId()] = true;     // key prevents doubles, value is useless
    }

    /**
     * @param \databox $databox    databox to index
     * @throws \Exception
     */
    public function indexScheduledRecords(databox $databox)
    {
        $this->recordIndexer->indexScheduled($databox);
    }

    public function flushQueue()
    {
        foreach($this->databoxToolbox as $sbas_id => $toolbox) {
            $q = &$this->getQueuesForDatabox($sbas_id);

            // it's useless to index records that are to be deleted, remove em from the index q.
            $q['index'] = array_diff_key($q['index'], $q['delete']);

            // Skip if nothing to do
            if (empty($q['index']) && empty($q['delete'])) {
                continue;
            }

            $databox = $this->appbox->get_databox($sbas_id);
            if(!empty($q['index'])) {
                $this->recordIndexer->index($databox, array_keys($q['index']));
                $q['index'] = [];
            }
            if(!empty($q['delete'])) {
                $this->recordIndexer->delete($databox, array_keys($q['delete']));
                $q['delete'] = [];
            }
        }
    }

    public function getSettings(array $params)
    {
        try {
            //Get setting from index
            return $this->client->indices()->getSettings($params);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
