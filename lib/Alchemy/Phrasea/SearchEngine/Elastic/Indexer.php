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

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\MergeException;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use Psr\Log\LoggerInterface;
use Elasticsearch\Client;
use media_subdef;
use Exception;
use igorw;

class Indexer
{
    /** @var \Elasticsearch\Client */
    private $client;
    private $options;
    private $logger;
    private $appbox;

    private $previousRefreshInterval = self::DEFAULT_REFRESH_INTERVAL;

    const DEFAULT_REFRESH_INTERVAL = '1s';
    const REFRESH_INTERVAL_KEY = 'index.refresh_interval';

    const TYPE_RECORD = 'record';
    const TYPE_TERM   = 'term';

    public function __construct(Client $client, array $options, LoggerInterface $logger, \appbox $appbox)
    {
        $this->client = $client;
        $this->options = $options;
        $this->logger = $logger;
        $this->appbox = $appbox;
    }

    public function createIndex($withMapping = true)
    {
        $params = array();
        $params['index'] = $this->options['index'];
        $params['body']['settings']['number_of_shards'] = $this->options['shards'];
        $params['body']['settings']['number_of_replicas'] = $this->options['replicas'];

        $params['body']['settings']['analysis'] = $this->getAnalysis();;

        if ($withMapping) {
            // TODO Move term/record mapping logic in TermIndexer and a new RecordIndexer
            $params['body']['mappings'][self::TYPE_RECORD] = $this->getRecordMapping();
            $params['body']['mappings'][self::TYPE_TERM]   = $this->getTermMapping();
        }
        $this->client->indices()->create($params);
    }

    public function updateMapping()
    {
        $params = array();
        $params['index'] = $this->options['index'];
        $params['type'] = self::TYPE_RECORD;
        $params['body'][self::TYPE_RECORD] = $this->getRecordMapping();

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

    public function populateIndex()
    {
        $this->disableShardRefreshing();

        try {
            // Prepare the bulk operation
            $bulk = new BulkOperation($this->client);
            $bulk->setDefaultIndex($this->options['index']);
            $bulk->setDefaultType(self::TYPE_RECORD);
            $bulk->setAutoFlushLimit(1000);

            // Helper to fetch record related data
            $recordHelper = new RecordHelper($this->appbox);

            foreach ($this->appbox->get_databoxes() as $databox) {
                // Update thesaurus terms index
                $termIndexer = new TermIndexer($this->client, $this->options, $databox);
                // TODO Pass a BulkOperation object to TermIndexer to muliplex
                // indexing queries between types
                $termIndexer->populateIndex();
                // TODO Create object to query thesaurus for term paths/synonyms
                // TODO Extract record indexing logic in a RecordIndexer class
                $fetcher = new RecordFetcher($databox, $recordHelper);
                $fetcher->setBatchSize(200);
                while ($record = $fetcher->fetch()) {
                    $params = array();
                    $params['id'] = $record['id'];
                    $params['body'] = $record;
                    $bulk->index($params);
                }
            }

            $bulk->flush();

            // Optimize index
            $params = array('index' => $this->options['index']);
            $this->client->indices()->optimize($params);
            $this->restoreShardRefreshing();
        } catch (Exception $e) {
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

    private function getTermMapping()
    {
        $mapping = new Mapping();
        $mapping
            ->add('value', 'string')
            ->add('context', 'string')
            ->add('path', 'string')
            ->add('lang', 'string')->notAnalyzed()
            ->add('databox_id', 'integer')
        ;

        return $mapping->export();
    }

    private function getRecordMapping()
    {
        $mapping = new Mapping();
        $mapping
            // Identifiers
            ->add('record_id', 'integer')  // Compound primary key
            ->add('databox_id', 'integer') // Compound primary key
            ->add('base_id', 'integer') // Unique collection ID
            ->add('collection_id', 'integer') // Useless collection ID (local to databox)
            ->add('uuid', 'string')->notAnalyzed()
            ->add('sha256', 'string')->notAnalyzed()
            // Mandatory metadata
            ->add('original_name', 'string')->notAnalyzed()
            ->add('mime', 'string')->notAnalyzed()
            ->add('type', 'string')->notAnalyzed()
            // Dates
            ->add('created_at', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
            ->add('updated_at', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
        ;

        // Caption mapping
        $captionMapping = new Mapping();
        $mapping->add('caption', $captionMapping);
        $privateCaptionMapping = new Mapping();
        $mapping->add('private_caption', $privateCaptionMapping);
        foreach ($this->getRecordFieldsStructure() as $name => $params) {
            $m = $params['private'] ? $privateCaptionMapping : $captionMapping;
            $m->add($name, $params['type']);

            if ($params['type'] === Mapping::TYPE_DATE) {
                $m->format(Mapping::DATE_FORMAT_CAPTION);
            }

            if (!$params['indexable'] && !$params['to_aggregate']) {
                $m->notIndexed();
            } elseif (!$params['indexable'] && $params['to_aggregate']) {
                $m->notAnalyzed();
                $m->addRawVersion();
            } else {
                $m->addRawVersion();
                $m->addAnalyzedVersion(['fr', 'de']); // @todo Dynamic list for the box
            }
        }

        // EXIF
        $mapping->add('exif', $this->getRecordExifMapping());

        // Status
        $mapping->add('flags', $this->getRecordFlagsMapping());

        return $mapping->export();
    }

    private function getRecordFieldsStructure()
    {
        $fields = array();

        foreach ($this->appbox->get_databoxes() as $databox) {
            printf("Databox %d\n", $databox->get_sbas_id());
            foreach ($databox->get_meta_structure() as $fieldStructure) {
                $field = array();
                // Field type
                switch ($fieldStructure->get_type()) {
                    case \databox_field::TYPE_DATE:
                        $field['type'] = 'date';
                        break;
                    case \databox_field::TYPE_NUMBER:
                        $field['type'] = 'string'; // TODO integer, float, double ?
                        break;
                    case \databox_field::TYPE_STRING:
                    case \databox_field::TYPE_TEXT:
                        $field['type'] = 'string';
                        break;
                    default:
                        throw new Exception(sprintf('Invalid field type "%s", expected "date", "number" or "string".', $fieldStructure->get_type()));
                        break;
                }

                // Business rules
                $field['private'] = $fieldStructure->isBusiness();
                $field['indexable'] = $fieldStructure->is_indexable();
                $field['to_aggregate'] = false; // @todo

                $name = $fieldStructure->get_name();

                printf("Field \"%s\" <%s> (private: %b)\n", $name, $field['type'], $field['private']);

                // Since mapping is merged between databoxes, two fields may
                // have conflicting names. Indexing is the same for a given
                // type so we reject only those with different types.
                if (isset($fields[$name])) {
                    if ($fields[$name]['type'] !== $field['type']) {
                        throw new MergeException(sprintf("Field %s can't be merged, incompatible types (%s vs %s)", $name, $fields[$name]['type'], $field['type']));
                    }

                    if ($fields[$name]['indexable'] !== $field['indexable']) {
                        throw new MergeException(sprintf("Field %s can't be merged, incompatible indexable state", $name));
                    }

                    if ($fields[$name]['to_aggregate'] !== $field['to_aggregate']) {
                        throw new MergeException(sprintf("Field %s can't be merged, incompatible to_aggregate state", $name));
                    }
                    // TODO other structure incompatibilities

                    printf("Merged with previous \"%s\" field\n", $name);
                }

                $fields[$name] = $field;
            }
        }

        return $fields;
    }

    // @todo Add call to addAnalyzedVersion ?
    private function getRecordExifMapping()
    {
        $mapping = new Mapping();
        $mapping
            ->add(media_subdef::TC_DATA_WIDTH, 'integer')
            ->add(media_subdef::TC_DATA_HEIGHT, 'integer')
            ->add(media_subdef::TC_DATA_COLORSPACE, 'string')->notAnalyzed()
            ->add(media_subdef::TC_DATA_CHANNELS, 'integer')
            ->add(media_subdef::TC_DATA_ORIENTATION, 'integer')
            ->add(media_subdef::TC_DATA_COLORDEPTH, 'integer')
            ->add(media_subdef::TC_DATA_DURATION, 'float')
            ->add(media_subdef::TC_DATA_AUDIOCODEC, 'string')->notAnalyzed()
            ->add(media_subdef::TC_DATA_AUDIOSAMPLERATE, 'integer')
            ->add(media_subdef::TC_DATA_VIDEOCODEC, 'string')->notAnalyzed()
            ->add(media_subdef::TC_DATA_FRAMERATE, 'float')
            ->add(media_subdef::TC_DATA_MIMETYPE, 'string')->notAnalyzed()
            ->add(media_subdef::TC_DATA_FILESIZE, 'long')
            // TODO use geo point type for lat/long
            ->add(media_subdef::TC_DATA_LONGITUDE, 'float')
            ->add(media_subdef::TC_DATA_LATITUDE, 'float')
            ->add(media_subdef::TC_DATA_FOCALLENGTH, 'float')
            ->add(media_subdef::TC_DATA_CAMERAMODEL, 'string')
            ->add(media_subdef::TC_DATA_FLASHFIRED, 'boolean')
            ->add(media_subdef::TC_DATA_APERTURE, 'float')
            ->add(media_subdef::TC_DATA_SHUTTERSPEED, 'float')
            ->add(media_subdef::TC_DATA_HYPERFOCALDISTANCE, 'float')
            ->add(media_subdef::TC_DATA_ISO, 'integer')
            ->add(media_subdef::TC_DATA_LIGHTVALUE, 'float')
        ;

        return $mapping;
    }

    private function getRecordFlagsMapping()
    {
        $mapping = new Mapping();
        $seen = array();

        foreach ($this->appbox->get_databoxes() as $databox) {
            foreach ($databox->get_statusbits() as $bit => $status) {
                $key = self::normalizeFlagKey($status['labelon']);
                // We only add to mapping new statuses
                if (!in_array($key, $seen)) {
                    $mapping->add($key, 'boolean');
                    $seen[] = $key;
                }
            }
        }

        return $mapping;
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
                    'filter'    => ['nfkc_normalizer', 'lowercase']
                ],
                // Lang specific
                'fr_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer', // better support for some Asian languages and using custom rules to break Myanmar and Khmer text.
                    'filter'    => ['nfkc_normalizer', 'lowercase', 'stop_fr', 'stem_fr']
                ],
                'en_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'lowercase', 'stop_en', 'stem_en']
                ],
                'de_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'lowercase', 'stop_de', 'stem_de']
                ],
                'nl_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'lowercase', 'stop_nl', 'stem_nl_override', 'stem_nl']
                ],
                'es_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'lowercase', 'stop_es', 'stem_es']
                ],
                'ar_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'lowercase', 'stop_ar', 'stem_ar']
                ],
                'ru_full' => [
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'lowercase', 'stop_ru', 'stem_ru']
                ],
                'cn_full' => [ // Standard chinese analyzer is not exposed
                    'type'      => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter'    => ['nfkc_normalizer', 'lowercase']
                ]
            ],
            'filter' => [
                'nfkc_normalizer' => [ // weißkopfseeadler => weisskopfseeadler, ١٢٣٤٥ => 12345.
                    'type' => 'icu_normalizer', // œ => oe, and use the fewest  bytes possible.
                    'name' => 'nfkc_cf' // nfkc_cf do the asciifolding job too.
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

    private static function normalizeFlagKey($key)
    {
        // Replace non letter or digits by _
        $key = preg_replace('/[^\\pL\d]+/u', '_', $key);
        $key = trim($key, '_');

        // Transliterate
        if (function_exists('iconv')) {
            $key = iconv('UTF-8', 'ASCII//TRANSLIT', $key);
        }

        // Remove non wording characters
        $key = preg_replace('/[^-\w]+/', '', $key);
        $key = strtolower($key);

        return $key;
    }
}
