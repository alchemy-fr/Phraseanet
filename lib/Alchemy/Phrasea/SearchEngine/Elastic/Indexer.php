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
use Symfony\Component\Stopwatch\Stopwatch;
use SplObjectStorage;

class Indexer
{
    const THESAURUS = 1;
    const RECORDS   = 2;

    /** @var \Elasticsearch\Client */
    private $client;
    private $options;
    private $appbox;

    private $recordIndexer;
    private $termIndexer;

    private $indexQueue;
    private $deleteQueue;

    private $previousRefreshInterval = self::DEFAULT_REFRESH_INTERVAL;

    const DEFAULT_REFRESH_INTERVAL = '1s';
    const REFRESH_INTERVAL_KEY = 'index.refresh_interval';

    public function __construct(Client $client, array $options, TermIndexer $termIndexer, RecordIndexer $recordIndexer, appbox $appbox)
    {
        $this->client   = $client;
        $this->options  = $options;
        $this->termIndexer = $termIndexer;
        $this->recordIndexer = $recordIndexer;
        $this->appbox   = $appbox;

        $this->indexQueue = new SplObjectStorage();
        $this->deleteQueue = new SplObjectStorage();
    }

    public function createIndex($withMapping = true)
    {
        $params = array();
        $params['index'] = $this->options['index'];
        $params['body']['settings']['number_of_shards'] = $this->options['shards'];
        $params['body']['settings']['number_of_replicas'] = $this->options['replicas'];
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
        $params['index'] = $this->options['index'];
        $params['type'] = RecordIndexer::TYPE_NAME;
        $params['body'][RecordIndexer::TYPE_NAME] = $this->recordIndexer->getMapping();

        // @todo Add term mapping

        // @todo This must throw a new indexation if a mapping is edited
        $this->client->indices()->putMapping($params);
    }

    public function deleteIndex()
    {
        $params = array('index' => $this->options['index']);
        $this->client->indices()->delete($params);
    }

    public function indexExists()
    {
        $params = array('index' => $this->options['index']);

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
            }

            if ($what & self::RECORDS) {
                $this->recordIndexer->populateIndex($bulk, $databoxes);

                // Final flush
                $bulk->flush();
            }

            // Optimize index
            $params = array('index' => $this->options['index']);
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

    public function indexScheduledRecords()
    {
        $this->apply(function(BulkOperation $bulk) {
            $this->recordIndexer->indexScheduled($bulk);
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
    }

    private function apply(Closure $work)
    {
        $this->disableShardRefreshing();

        try {
            // Prepare the bulk operation
            $bulk = new BulkOperation($this->client);
            $bulk->setDefaultIndex($this->options['index']);
            $bulk->setAutoFlushLimit(1000);
            // Do the work
            $work($bulk);
            // Flush just in case, it's a noop when already done
            $bulk->flush();
            $this->restoreShardRefreshing();
        } catch (\Exception $e) {
            $this->restoreShardRefreshing();
            throw $e;
        }
    }

    private function disableShardRefreshing()
    {
        $refreshInterval = $this->getSetting(self::REFRESH_INTERVAL_KEY);
        if (null !== $refreshInterval) {
            $this->previousRefreshInterval = $refreshInterval;
        }
        $this->setSetting(self::REFRESH_INTERVAL_KEY, "30s");
    }

    private function restoreShardRefreshing()
    {
        $this->setSetting(self::REFRESH_INTERVAL_KEY, $this->previousRefreshInterval);
        $this->previousRefreshInterval = self::DEFAULT_REFRESH_INTERVAL;
    }

    private function getSetting($name)
    {
        $index = $this->options['index'];
        $params = array();
        $params['index'] = $index;
        $params['name'] = $name;
        $params['flat_settings'] = true;
        $response = $this->client->indices()->getSettings($params);

        return igorw\get_in($response, [$index, 'settings', $name]);
    }

    private function setSetting($name, $value)
    {
        $index = $this->options['index'];
        $params = array();
        $params['index'] = $index;
        $params['body'][$name] = $value;
        $response = $this->client->indices()->putSettings($params);

        return igorw\get_in($response, ['acknowledged']);
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
