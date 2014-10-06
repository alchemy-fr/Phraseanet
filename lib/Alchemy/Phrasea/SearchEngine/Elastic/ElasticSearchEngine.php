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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Serializer\ESRecordSerializer;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Elasticsearch\Client;

class ElasticSearchEngine implements SearchEngineInterface
{
    private $app;
    /** @var Client */
    private $client;

    private $dateFields;
    private $indexName;
    private $serializer;
    private $configurationPanel;

    public function __construct(Application $app, Client $client, ESRecordSerializer $serializer, $indexName)
    {
        $this->app = $app;
        $this->client = $client;
        $this->serializer = $serializer;

        if ('' === trim($indexName)) {
            throw new \InvalidArgumentException('The provided index name is invalid.');
        }

        $this->indexName = $indexName;
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ElasticSearch';
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        $data = $this->client->info();
        $version = $data['version'];
        unset($data['version']);

        foreach ($version as $prop => $value) {
            $data['version:'.$prop] = $value;
        }

        $ret = [];

        foreach ($data as $key => $value) {
            $ret[] = [$key, $value];
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationPanel()
    {
        if (!$this->configurationPanel) {
            $this->configurationPanel = new ConfigurationPanel($this, $this->app['conf']);
        }

        return $this->configurationPanel;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableDateFields()
    {
        if (!$this->dateFields) {
            foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
                foreach ($databox->get_meta_structure() as $databox_field) {
                    if ($databox_field->get_type() != \databox_field::TYPE_DATE) {
                        continue;
                    }

                    $this->dateFields[] = $databox_field->get_name();
                }
            }

            $this->dateFields = array_unique($this->dateFields);
        }

        return $this->dateFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableSort()
    {
        return [
            'score' => $this->app->trans('pertinence'),
            'created_on' => $this->app->trans('date dajout'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return 'score';
    }

    /**
     * {@inheritdoc}
     */
    public function isStemmingEnabled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableOrder()
    {
        return [
            'desc' => $this->app->trans('descendant'),
            'asc'  => $this->app->trans('ascendant'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function hasStemming()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTypes()
    {
        return [self::GEM_TYPE_RECORD, self::GEM_TYPE_STORY];
    }

    /**
     * {@inheritdoc}
     */
    public function addRecord(\record_adapter $record)
    {
        $this->doExecute('index', [
            'body'  => $this->serializer->serialize($record),
            'index' => $this->indexName,
            'type'  => 'record',
            'id'    => sprintf('%d-%d', $record->get_sbas_id(), $record->get_record_id()),
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRecord(\record_adapter $record)
    {
        $this->doExecute('delete', [
            'index' => $this->indexName,
            'type'  => 'record',
            'id'    => sprintf('%s-%s', $record->get_sbas_id(), $record->get_record_id()),
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updateRecord(\record_adapter $record)
    {
        $this->addRecord($record);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addStory(\record_adapter $story)
    {
        $this->addRecord($story);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeStory(\record_adapter $story)
    {
        $this->removeRecord($story);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStory(\record_adapter $story)
    {
        $this->addRecord($story);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addFeedEntry(FeedEntry $entry)
    {
        throw new RuntimeException('ElasticSearch engine does not support feed entry indexing.');
    }

    /**
     * {@inheritdoc}
     */
    public function removeFeedEntry(FeedEntry $entry)
    {
        throw new RuntimeException('ElasticSearch engine does not support feed entry indexing.');
    }

    /**
     * {@inheritdoc}
     */
    public function updateFeedEntry(FeedEntry $entry)
    {
        throw new RuntimeException('ElasticSearch engine does not support feed entry indexing.');
    }

    /**
     * {@inheritdoc}
     */
    public function query($string, $offset, $perPage, SearchEngineOptions $options = null)
    {
        $parser = new QueryParser();
        $ast = $parser->parse($string);
        $query = $ast->getQuery();

        // $query = 'all' !== strtolower($query) ? $query : '';
        // $params = $this->createQueryParams($query, $options ?: new SearchEngineOptions());
        // $params['from'] = $offset;
        // $params['size'] = $perPage;

        // $res = $this->doExecute('search', $params);

        $results = new ArrayCollection();
        $suggestions = new ArrayCollection();
        // $n = 0;

        // foreach ($res['hits']['hits'] as $hit) {
        //     $databoxId = is_array($hit['fields']['databox_id']) ? array_pop($hit['fields']['databox_id']) : $hit['fields']['databox_id'];
        //     $recordId = is_array($hit['fields']['record_id']) ? array_pop($hit['fields']['record_id']) : $hit['fields']['record_id'];
        //     $results[] = new \record_adapter($this->app, $databoxId, $recordId, $n++);
        // }

        $query['_ast'] = (string) $ast;

        return new SearchEngineResult($results, json_encode($query), null, null, null, null, null, null, $suggestions, [], $this->indexName);
    }

    /**
     * {@inheritdoc}
     */
    public function autocomplete($query, SearchEngineOptions $options)
    {
        throw new RuntimeException('ElasticSearch engine currently does not support auto-complete.');
    }

    /**
     * {@inheritdoc}
     */
    public function excerpt($query, $fields, \record_adapter $record, SearchEngineOptions $options = null)
    {
        //@todo implements

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function resetCache()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clearAllCache(\DateTime $date = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function createSubscriber(Application $app)
    {
        return new ElasticSearchEngineSubscriber();
    }

    /**
     * {@inheritdoc}
     *
     * @return ElastcSearchEngine
     */
    public static function create(Application $app, array $options = [])
    {
        $options = array_replace([
            'host'  => '127.0.0.1',
            'port'  => '9200',
            'index' => 'phraseanet',
        ], $options);

        $client = new Client(['hosts' => [sprintf('%s:%s', $options['host'], $options['port'])]]);

        return new static($app, $client, $app['serializer.es-record'], $options['index']);
    }

    private function createQueryParams($query, SearchEngineOptions $options, \record_adapter $record = null)
    {
        $params = [
            'index' => $this->indexName,
            'type'  => 'record',
            'body'  => [
                'fields' => ['databox_id', 'record_id'],
                'sort'   => $this->createSortQueryParams($options),
            ]
        ];

        $ESquery = $this->createESQuery($query, $options);
        $filters = $this->createFilters($options);

        if ($record) {
            $filters[] = [
                'term' => [
                    '_id' => sprintf('%s-%s', $record->get_sbas_id(), $record->get_record_id()),
                ]
            ];

            $fields = [];

            foreach ($record->get_databox()->get_meta_structure() as $dbField) {
                $fields['caption.'.$dbField->get_name()] = new \stdClass();
            }

            $params['body']['highlight'] = [
                "pre_tags"  => ["[[em]]"],
                "post_tags" => ["[[/em]]"],
                "fields"    => $fields,
            ];
        }

        if (count($filters) > 0) {
            $ESquery = [
                'filtered' => [
                    'query' => $ESquery,
                    'filter' => [
                        'and' => $filters
                    ]
                ]
            ];
        }

        $params['body']['query'] = $ESquery;

        return $params;
    }

    private function createESQuery($query, SearchEngineOptions $options)
    {
        // $preg = preg_match('/\s?(recordid|storyid)\s?=\s?([0-9]+)/i', $query, $matches, 0, 0);

        // $search = [];
        // if ($preg > 0) {
        //     $search['bool']['must'][] = [
        //         'term' => [
        //             'record_id' => $matches[2],
        //         ],
        //     ];
        //     $query = '';
        // }

        if ('' !== $query) {
            if (0 < count($options->getBusinessFieldsOn())) {
                $fields = [];

                foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
                    foreach ($databox->get_meta_structure() as $dbField) {
                        if ($dbField->isBusiness()) {
                            $fields[$dbField->get_name()] = [
                                'match' => [
                                    'caption.'.$dbField->get_name() => $query,
                                ]
                            ];
                        }
                    }
                }

                if (count($fields) > 0) {
                    foreach ($options->getBusinessFieldsOn() as $coll) {
                        $search['bool']['should'][] = [
                            'bool' => [
                                'must' => [
                                    [
                                        'bool' => [
                                            'should' => array_values($fields)
                                        ]
                                    ],[
                                        'term' => [
                                            'base_id' => $coll->get_base_id(),
                                        ]
                                    ]
                                ]
                            ]
                        ];
                    }
                }
            }

            if ($options->getFields()) {
                foreach ($options->getFields() as $field) {
                    $search['bool']['should'][] = [
                        'match' => [
                            'caption.'.$field->get_name() => $query,
                        ]
                    ];
                }
            } else {
                $search['bool']['should'][] = [
                    'match' => [
                        '_all' => $query,
                    ]
                ];
            }
        } else {
            $search['bool']['should'][] = [
                'match_all' => new \stdClass(),
            ];
        }

        return $search;
    }

    private function createFilters(SearchEngineOptions $options)
    {
        $filters = [];

        $status_opts = $options->getStatus();
        foreach ($options->getDataboxes() as $databox) {
            foreach ($databox->get_statusbits() as $n => $status) {
                if (!array_key_exists($n, $status_opts)) {
                    continue;
                }
                if (!array_key_exists($databox->get_sbas_id(), $status_opts[$n])) {
                    continue;
                }

                $filters[] = [
                    'term' => [
                        'status.status-'.$n => $status_opts[$n][$databox->get_sbas_id()],
                    ]
                ];
            }
        }

        $filters[] = [
            'terms' => [
                'base_id' => array_map(function (\collection $coll) { return $coll->get_base_id(); }, $options->getCollections())
            ]
        ];
        $filters[] = [
            'term' => [
                'type' => $options->getSearchType() === SearchEngineOptions::RECORD_RECORD ? 'record' : 'story',
            ]
        ];

        if ($options->getDateFields() && ($options->getMaxDate() || $options->getMinDate())) {
            $range = [];
            if ($options->getMaxDate()) {
                $range['lte'] = $options->getMaxDate()->format(DATE_ATOM);
            }
            if ($options->getMinDate()) {
                $range['gte'] = $options->getMinDate()->format(DATE_ATOM);
            }

            foreach ($options->getDateFields() as $dateField) {
                $filters[] = [
                    'range' => [
                        'caption.'.$dateField->get_name() => $range
                    ]
                ];
            }
        }

        if ($options->getRecordType()) {
            $filters[] = [
                'term' => [
                    'phrasea_type' => $options->getRecordType(),
                ]
            ];
        }

        return $filters;
    }

    private function createSortQueryParams(SearchEngineOptions $options)
    {
        $sort = [];
        if ($options->getSortBy() === 'score') {
            $sort['_score'] = $options->getSortOrder();
        }

        $sort['created_on'] = $options->getSortOrder();

        return $sort;
    }

    private function doExecute($method, array $params)
    {
        $res = call_user_func([$this->client, $method], $params);

        if (isset($res['error'])) {
            throw new RuntimeException('Unable to execute method '.$method);
        }

        return $res;
    }
}
