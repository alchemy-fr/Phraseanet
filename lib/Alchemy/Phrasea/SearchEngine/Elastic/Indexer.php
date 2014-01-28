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

use Psr\Log\LoggerInterface;

class Indexer
{
    private $engine;
    private $logger;
    private $appbox;

    public function __construct(ElasticSearchEngine $engine, LoggerInterface $logger, \appbox $appbox)
    {
        $this->engine = $engine;
        $this->logger = $logger;
        $this->appbox = $appbox;
    }

    public function createIndex()
    {
        $indexParams['index'] = $this->engine->getIndexName();

        // Index Settings
        $indexParams['body']['settings']['number_of_shards']   = 3;
        $indexParams['body']['settings']['number_of_replicas'] = 0;

        $captionFields = [];
        $businessFields = [];

        foreach ($this->appbox->get_databoxes() as $databox) {
            foreach ($databox->get_meta_structure() as $dbField) {
                $type = 'string';
                if (\databox_field::TYPE_DATE === $dbField->get_type()) {
                    $type = 'date';
                }
                if (isset($captionFields[$dbField->get_name()]) && $type !== $captionFields[$dbField->get_name()]['type']) {
                    $type = 'string';
                }

                $captionFields[$dbField->get_name()] = [
                    'type'           => $type,
                    'include_in_all' => !$dbField->isBusiness(),
                    'analyzer'       => 'french',
                ];

                if ($dbField->isBusiness()) {
                    $businessFields[$dbField->get_name()] = [
                        'type'           => $type,
                        'include_in_all' => false,
                        'analyzer'       => 'french',
                    ];
                }
            }
        }

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
            ],
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

        $indexParams['body']['mappings']['record'] = $recordTypeMapping;

        if ($this->engine->getClient()->indices()->exists(['index' => $this->engine->getIndexName()])) {
            $this->engine->getClient()->indices()->delete(['index' => $this->engine->getIndexName()]);
        }

        $ret = $this->engine->getClient()->indices()->create($indexParams);

        if (isset($ret['error']) || !$ret['ok']) {
            throw new \RuntimeException('Unable to create index');
        }
    }

    public function reindexAll()
    {
        $qty = 10;

        $params['index'] = $this->engine->getIndexName();
        $params['body']['index']['refresh_interval'] = 300;

        $ret = $this->engine->getClient()->indices()->putSettings($params);

        if (!isset($ret['ok']) || !$ret['ok']) {
            $this->logger->error('Unable to set the refresh interval to 300 s. .');
        }

        foreach ($this->appbox->get_databoxes() as $databox) {
            $offset = 0;
            do {
                $sql = 'SELECT record_id FROM record
                        WHERE parent_record_id = 0
                        ORDER BY record_id ASC LIMIT '.$offset.', '.$qty;
                $stmt = $databox->get_connection()->prepare($sql);
                $stmt->execute();
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                foreach ($rows as $row) {
                    $record = $databox->get_record($row['record_id']);
                    $this->engine->addRecord($record);
                }

                gc_collect_cycles();
                $offset += $qty;
            } while (count($rows) > 0);
        }

        $params['index'] = $this->engine->getIndexName();
        $params['body']['index']['refresh_interval'] = 1;

        $ret = $this->engine->getClient()->indices()->putSettings($params);

        if (!isset($ret['ok']) || !$ret['ok']) {
            throw new \RuntimeException('Unable to set the refresh interval to 1 s. .');
        }
    }
}
