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
use Symfony\Component\Stopwatch\Stopwatch;
use SplObjectStorage;

class Indexer
{
    const THESAURUS = 1;
    const RECORDS   = 2;

    /** @var \Elasticsearch\Client */
    private $client;
    /** @var ElasticsearchOptions */
    private $options;
    private $appbox;
    /** @var LoggerInterface|null */
    private $logger;

    private $recordIndexer;
    private $termIndexer;

    private $indexQueue;        // contains RecordInterface(s)
    private $deleteQueue;

    public function __construct(Client $client, ElasticsearchOptions $options, TermIndexer $termIndexer, RecordIndexer $recordIndexer, appbox $appbox, LoggerInterface $logger = null)
    {
        $this->client   = $client;
        $this->options  = $options;
        $this->termIndexer = $termIndexer;
        $this->recordIndexer = $recordIndexer;
        $this->appbox   = $appbox;
        $this->logger = $logger ?: new NullLogger();

        $this->indexQueue = new SplObjectStorage();
        $this->deleteQueue = new SplObjectStorage();
    }

    public function createIndex($withMapping = true)
    {
        $params = array();
        $params['index'] = $this->options->getIndexName();
        $params['body']['settings']['number_of_shards'] = $this->options->getShards();
        $params['body']['settings']['number_of_replicas'] = $this->options->getReplicas();
        $params['body']['settings']['analysis'] = $this->getAnalysis();;

        if ($withMapping) {
            $params['body']['mappings'][RecordIndexer::TYPE_NAME] = $this->recordIndexer->getMapping();
            $params['body']['mappings'][TermIndexer::TYPE_NAME]   = $this->termIndexer->getMapping();
        }

        $this->client->indices()->create($params);
    }

    public function updateMapping()
    {
        $params = array();
        $params['index'] = $this->options->getIndexName();
        $params['type'] = RecordIndexer::TYPE_NAME;
        $params['body'][RecordIndexer::TYPE_NAME] = $this->recordIndexer->getMapping();
        $params['body'][TermIndexer::TYPE_NAME]   = $this->termIndexer->getMapping();

        // @todo This must throw a new indexation if a mapping is edited
        $this->client->indices()->putMapping($params);
    }

    public function deleteIndex()
    {
        $params = array('index' => $this->options->getIndexName());
        $this->client->indices()->delete($params);
    }

    public function indexExists()
    {
        $params = array('index' => $this->options->getIndexName());

        return $this->client->indices()->exists($params);
    }

    public function populateIndex($what, array $databoxes_id = [])
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('populate');

        if ($databoxes_id) {
            // If databoxes are given, only use those
            $databoxes = array_map(array($this->appbox, 'get_databox'), $databoxes_id);
        } else {
            $databoxes = $this->appbox->get_databoxes();
        }

        $this->apply(function(BulkOperation $bulk) use ($what, $databoxes) {
            if ($what & self::THESAURUS) {
                $this->termIndexer->populateIndex($bulk, $databoxes);

                // Record indexing depends on indexed terms so we need to make
                // everything ready to search
                $bulk->flush();
                $this->client->indices()->refresh();
                $this->client->indices()->clearCache();
                $this->client->indices()->flushSynced();
            }

            if ($what & self::RECORDS) {
                $this->recordIndexer->populateIndex($bulk, $databoxes);

                // Final flush
                $bulk->flush();
            }

            // Optimize index
            $params = array('index' => $this->options->getIndexName());
            $this->client->indices()->optimize($params);
        });

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
     * @param \databox[] $databoxes    databoxes to index
     * @throws \Exception
     */
    public function indexScheduledRecords(array $databoxes)
    {
        $this->apply(function(BulkOperation $bulk) use($databoxes) {
            $this->recordIndexer->indexScheduled($bulk, $databoxes);
        });
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
        });

        $this->indexQueue = new SplObjectStorage();
        $this->deleteQueue = new SplObjectStorage();
    }

    private function apply(Closure $work)
    {
        // Prepare the bulk operation
        $bulk = new BulkOperation($this->client, $this->logger);
        $bulk->setDefaultIndex($this->options->getIndexName());
        $bulk->setAutoFlushLimit(1000);
        // Do the work
        $work($bulk);
        // Flush just in case, it's a noop when already done
        $bulk->flush();
    }

    /**
     * Editing this configuration must be followed by a full re-indexation
     * @return array
     */
    private function getAnalysis()
    {
        return [
            'analyzer' => [
                // General purpose, without removing stop word or stem: improve meaning accuracy
                'general_light' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    // TODO Maybe replace nfkc_normalizer + asciifolding with icu_folding
                    'filter'    => ['nfkc_normalizer', 'asciifolding']
                ],
                // Lang specific
                'fr_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer', // better support for some Asian languages and using custom rules to break Myanmar and Khmer text.
                    'filter'    => ['nfkc_normalizer', 'asciifolding', 'elision', 'stop_fr', 'stem_fr']
                ],
                'en_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'asciifolding', 'stop_en', 'stem_en']
                ],
                'de_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'asciifolding', 'stop_de', 'stem_de']
                ],
                'nl_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'asciifolding', 'stop_nl', 'stem_nl_override', 'stem_nl']
                ],
                'es_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'asciifolding', 'stop_es', 'stem_es']
                ],
                'ar_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'asciifolding', 'stop_ar', 'stem_ar']
                ],
                'ru_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'asciifolding', 'stop_ru', 'stem_ru']
                ],
                'cn_full' => [ // Standard chinese analyzer is not exposed
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'asciifolding']
                ],
                // Thesaurus specific
                'thesaurus_path' => [
                    'type'      => 'custom',
                    'tokenizer' => 'thesaurus_path'
                ],
                // Thesaurus strict term lookup
                'thesaurus_term_strict' => [
                    'type'      => 'custom',
                    'tokenizer' => 'keyword',
                    'filter'    => 'nfkc_normalizer'
                ]
            ],
            'tokenizer' => [
                'thesaurus_path' => [
                    'type' => 'path_hierarchy'
                ]
            ],
            'filter' => [
                'nfkc_normalizer' => [ // weißkopfseeadler => weisskopfseeadler, ١٢٣٤٥ => 12345.
                    'type' => 'icu_normalizer', // œ => oe, and use the fewest  bytes possible.
                    'name' => 'nfkc_cf' // nfkc_cf do the lowercase job too.
                ],

                'stop_fr' => [
                    'type' => 'stop',
                    'stopwords' => ['l', 'm', 't', 'qu', 'n', 's', 'j', 'd'],
                ],
                'stop_en' => [
                    'type' => 'stop',
                    'stopwords' => '_english_' // Use the Lucene default
                ],
                'stop_de' => [
                    'type' => 'stop',
                    'stopwords' => '_german_' // Use the Lucene default
                ],
                'stop_nl' => [
                    'type' => 'stop',
                    'stopwords' => '_dutch_' // Use the Lucene default
                ],
                'stop_es' => [
                    'type' => 'stop',
                    'stopwords' => '_spanish_' // Use the Lucene default
                ],
                'stop_ar' => [
                    'type' => 'stop',
                    'stopwords' => '_arabic_' // Use the Lucene default
                ],
                'stop_ru' => [
                    'type' => 'stop',
                    'stopwords' => '_russian_' // Use the Lucene default
                ],

                // See http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/analysis-stemmer-tokenfilter.html
                'stem_fr' => [
                    'type' => 'stemmer',
                    'name' => 'light_french',
                ],
                'stem_en' => [
                    'type' => 'stemmer',
                    'name' => 'english', // Porter stemming algorithm
                ],
                'stem_de' => [
                    'type' => 'stemmer',
                    'name' => 'light_german',
                ],
                'stem_nl' => [
                    'type' => 'stemmer',
                    'name' => 'dutch', // Snowball algo
                ],
                'stem_es' => [
                    'type' => 'stemmer',
                    'name' => 'light_spanish',
                ],
                'stem_ar' => [
                    'type' => 'stemmer',
                    'name' => 'arabic', // Lucene Arabic stemmer
                ],
                'stem_ru' => [
                    'type' => 'stemmer',
                    'name' => 'russian', // Snowball algo
                ],

                // Some custom rules
                'stem_nl_override' => [
                    'type' => 'stemmer_override',
                    'rules' => [
                        "fiets=>fiets",
                        "bromfiets=>bromfiets",
                        "ei=>eier",
                        "kind=>kinder"
                    ]
                ]
            ],
        ];
    }
}
