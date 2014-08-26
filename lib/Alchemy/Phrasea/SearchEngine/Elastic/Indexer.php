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

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use igorw;

class Indexer
{
    /** @var Elasticsearch\Client */
    private $client;
    private $options;
    private $engine;
    private $logger;
    private $appbox;

    private $previousRefreshInterval = self::DEFAULT_REFRESH_INTERVAL;

    const DEFAULT_REFRESH_INTERVAL = '1s';
    const REFRESH_INTERVAL_KEY = 'index.refresh_interval';

    const RECORD_TYPE = 'record';

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
        if ($withMapping) {
            $params['body']['mappings'][self::RECORD_TYPE] = $this->getRecordMapping();
        }
        $this->client->indices()->create($params);
    }

    public function updateMapping()
    {
        $params = array();
        $params['index'] = $this->options['index'];
        $params['type'] = self::RECORD_TYPE;
        $params['body'][self::RECORD_TYPE] = $this->getRecordMapping();
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
            foreach ($this->appbox->get_databoxes() as $databox) {
                $fetcher = new RecordFetcher($databox);
                $fetcher->setBatchSize(200);
                while ($record = $fetcher->fetch()) {
                    $params = array();
                    $params['index'] = $this->options['index'];
                    $params['type'] = self::RECORD_TYPE;
                    $params['id'] = $record['id'];
                    $params['body'] = $record;
                    $response = $this->client->index($params);
                }
            }

            // Optimize index
            $params = array('index' => $this->options['index']);
            $this->client->indices()->optimize($params);

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
        $this->setSetting(self::REFRESH_INTERVAL_KEY, -1);
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
            ->add('created_at', 'date')->format('yyyy-MM-dd HH:mm:ss')
            ->add('updated_at', 'date')->format('yyyy-MM-dd HH:mm:ss')
        ;

        return $mapping->export();


        // TODO Migrate code below this line

        $status = [];
        for ($i = 0; $i <= 32; $i ++) {
            $status['status-'.$i] = [
                'type' => 'integer',
            ];
        }

        $recordTypeMapping = [
            '_source' => [
                'enabled' => true
            ],
            '_all' => [
                'analyzer' => 'french',
                'analysis' => [
                    'analyzer' => [
                        'french' => [
                            'type'      => 'custom',
                            'tokenizer' => 'letter',
                            'filter'    => ["asciifolding", "lowercase", "french_stem", "stop_fr"]
                        ],
                        'autocomplete_french' => [
                            'type'      => 'custom',
                            'tokenizer' => 'letter',
                            'filter'    => ["asciifolding", "lowercase", "stop_fr"]
                        ]
                    ],
                    'filter' => [
                        'stop_fr' => [
                            'type' => 'stop',
                            'stopwords' => ['l', 'm', 't', 'qu', 'n', 's', 'j', 'd'],
                        ]
                    ],
                ]
            ],
            'properties' => [
                'record_id' => [
                    'type' => 'integer',
                    'index' => 'not_analyzed',
                ],
                'databox_id' => [
                    'type' => 'integer',
                    'index' => 'not_analyzed',
                ],
                'base_id' => [
                    'type' => 'integer',
                    'index' => 'not_analyzed',
                ],
                'mime_type' => [
                    'type' => 'string',
                    'index' => 'not_analyzed',
                ],
                'title' => [
                    'type' => 'string',
                    'index' => 'not_analyzed',
                ],
                'original_name' => [
                    'type' => 'string',
                    'index' => 'not_analyzed',
                ],
                'updated_on' => [
                    'type' => 'date',
                    'index' => 'not_analyzed',
                ],
                'created_on' => [
                    'type' => 'date',
                    'index' => 'not_analyzed',
                ],
                'collection_id' => [
                    'type' => 'integer',
                    'index' => 'not_analyzed',
                ],
                'sha256' => [
                    'type' => 'string',
                    'index' => 'not_analyzed',
                ],
                'type' => [
                    'type' => 'string',
                    'index' => 'not_analyzed',
                ],
                'phrasea_type' => [
                    'type' => 'string',
                    'index' => 'not_analyzed',
                ],
                'uuid' => [
                    'type' => 'string',
                    'index' => 'not_analyzed',
                ],
                'status' => [
                    'properties' => $status
                ],
                "technical_informations" => [
                    'properties' => [
                        \media_subdef::TC_DATA_WIDTH => [
                            'type' => 'integer'
                        ],
                        \media_subdef::TC_DATA_HEIGHT => [
                            'type' => 'integer'
                        ],
                        \media_subdef::TC_DATA_COLORSPACE => [
                            'type' => 'string'
                        ],
                        \media_subdef::TC_DATA_CHANNELS => [
                            'type' => 'integer'
                        ],
                        \media_subdef::TC_DATA_ORIENTATION => [
                            'type' => 'integer'
                        ],
                        \media_subdef::TC_DATA_COLORDEPTH => [
                            'type' => 'integer'
                        ],
                        \media_subdef::TC_DATA_DURATION => [
                            'type' => 'integer'
                        ],
                        \media_subdef::TC_DATA_AUDIOCODEC => [
                            'type' => 'string'
                        ],
                        \media_subdef::TC_DATA_AUDIOSAMPLERATE => [
                            'type' => 'integer'
                        ],
                        \media_subdef::TC_DATA_VIDEOCODEC => [
                            'type' => 'string'
                        ],
                        \media_subdef::TC_DATA_FRAMERATE => [
                            'type' => 'float'
                        ],
                        \media_subdef::TC_DATA_MIMETYPE => [
                            'type' => 'string'
                        ],
                        \media_subdef::TC_DATA_FILESIZE => [
                            'type' => 'long'
                        ],
                        \media_subdef::TC_DATA_LONGITUDE => [
                            'type' => 'float'
                        ],
                        \media_subdef::TC_DATA_LATITUDE => [
                            'type' => 'float'
                        ],
                        \media_subdef::TC_DATA_FOCALLENGTH => [
                            'type' => 'float'
                        ],
                        \media_subdef::TC_DATA_CAMERAMODEL => [
                            'type' => 'string'
                        ],
                        \media_subdef::TC_DATA_FLASHFIRED => [
                            'type' => 'boolean'
                        ],
                        \media_subdef::TC_DATA_APERTURE => [
                            'type' => 'float'
                        ],
                        \media_subdef::TC_DATA_SHUTTERSPEED => [
                            'type' => 'float'
                        ],
                        \media_subdef::TC_DATA_HYPERFOCALDISTANCE => [
                            'type' => 'float'
                        ],
                        \media_subdef::TC_DATA_ISO => [
                            'type' => 'integer'
                        ],
                        \media_subdef::TC_DATA_LIGHTVALUE => [
                            'type' => 'float'
                        ],
                    ]
                ],
                "caption" => [
                    'properties' => $captionFields
                ],
            ]
        ];

        if (0 < count ($businessFields)) {
            $recordTypeMapping['properties']['caption-business'] = [
                'properties' => $businessFields
            ];
        }
    }
}
