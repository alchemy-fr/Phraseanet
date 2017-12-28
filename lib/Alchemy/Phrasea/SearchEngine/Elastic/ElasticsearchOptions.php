<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\SearchEngine\Elastic;

class ElasticsearchOptions
{
    const POPULATE_ORDER_RID = "RECORD_ID";
    const POPULATE_ORDER_MODDATE = "MODIFICATION_DATE";
    const POPULATE_DIRECTION_ASC = "ASC";
    const POPULATE_DIRECTION_DESC = "DESC";
    /** @var string */
    private $host;
    /** @var int */
    private $port;
    /** @var string */
    private $indexName;
    /** @var int */
    private $shards;
    /** @var int */
    private $replicas;
    /** @var int */
    private $minScore;
    /** @var  bool */
    private $highlight;
    /** @var int */
    private $maxResultWindow;
    /** @var string */
    private $populateOrder;
    /** @var string */
    private $populateDirection;
    /** @var  int[] */
    private $_customValues;
    private $activeTab;

    /**
     * Factory method to hydrate an instance from serialized options
     *
     * @param array $options
     * @return self
     */
    public static function fromArray(array $options)
    {
        $defaultOptions = [
            'host'               => '127.0.0.1',
            'port'               => 9200,
            'index'              => '',
            'shards'             => 3,
            'replicas'           => 0,
            'minScore'           => 4,
            'highlight'          => true,
            'max_result_window'  => 500000,
            'populate_order'     => self::POPULATE_ORDER_RID,
            'populate_direction' => self::POPULATE_DIRECTION_DESC,
            'activeTab'          => null,
        ];
        foreach(self::getAggregableTechnicalFields() as $k => $f) {
            $defaultOptions[$k.'_limit'] = 0;
        }

        $options = array_replace($defaultOptions, $options);

        $self = new self();
        $self->setHost($options['host']);
        $self->setPort($options['port']);
        $self->setIndexName($options['index']);
        $self->setShards($options['shards']);
        $self->setReplicas($options['replicas']);
        $self->setMinScore($options['minScore']);
        $self->setHighlight($options['highlight']);
        $self->setMaxResultWindow($options['max_result_window']);
        $self->setPopulateOrder($options['populate_order']);
        $self->setPopulateDirection($options['populate_direction']);
        $self->setActiveTab($options['activeTab']);
        foreach(self::getAggregableTechnicalFields() as $k => $f) {
            $self->setAggregableFieldLimit($k, $options[$k.'_limit']);
        }

        return $self;
    }

    public static function getAggregableTechnicalFields()
    {
        return [
            'base_aggregate' => [
                'label' => 'prod::facet:base_label',
                'field' => 'databox_name',
                'query' => 'database:%s',
            ],
            'collection_aggregate' => [
                'label' => 'prod::facet:collection_label',
                'field' => 'collection_name',
                'query' => 'collection:%s',
            ],
            'doctype_aggregate' => [
                'label' => 'prod::facet:doctype_label',
                'field' => 'type',
                'query' => 'type:%s',
            ],
            'camera_model_aggregate' => [
                'label' => 'Camera Model',
                'field' => 'metadata_tags.CameraModel.raw',
                'query' => 'meta.CameraModel:%s',
            ],
            'iso_aggregate' => [
                'label' => 'ISO',
                'field' => 'metadata_tags.ISO',
                'query' => 'meta.ISO=%s',
            ],
            'aperture_aggregate' => [
                'label' => 'Aperture',
                'field' => 'metadata_tags.Aperture',
                'query' => 'meta.Aperture=%s',
                'output_formatter' => function($value) {
                    return round($value, 1);
                },
            ],
            'shutterspeed_aggregate' => [
                'label' => 'Shutter speed',
                'field' => 'metadata_tags.ShutterSpeed',
                'query' => 'meta.ShutterSpeed=%s',
                'output_formatter' => function($value) {
                    static $map = [
                        '0.50000' => '1/2',
                        '0.33333' => '1/3',
                        '0.25000' => '1/4',
                        '0.20000' => '1/5',
                        '0.12500' => '1/8',
                        '0.10000' => '1/10',
                        '0.06667' => '1/15',
                        '0.06250' => '1/16',
                        '0.05000' => '1/20',
                        '0.04000' => '1/25',
                        '0.03333' => '1/30',
                        '0.03125' => '1/32',
                        '0.02500' => '1/40',
                        '0.02000' => '1/50',
                        '0.01562' => '1/64',
                        '0.01250' => '1/80',
                        '0.01000' => '1/100',
                        '0.00800' => '1/125',
                        '0.00667' => '1/150',
                        '0.00625' => '1/160',
                        '0.00500' => '1/200',
                        '0.00400' => '1/250',
                        '0.00333' => '1/300',
                        '0.00313' => '1/320',
                        '0.00250' => '1/400',
                        '0.00200' => '1/500',
                        '0.00156' => '1/640',
                        '0.00125' => '1/800',
                        '0.00100' => '1/1000',
                        '0.00080' => '1/1250',
                        '0.00067' => '1/1500',
                        '0.00063' => '1/1600',
                        '0.00050' => '1/2000',
                        '0.00040' => '1/2500',
                        '0.00033' => '1/3000',
                        '0.00031' => '1/3200',
                        '0.00025' => '1/4000',
                        '0.00020' => '1/5000',
                        '0.00016' => '1/6400',
                        '0.00013' => '1/8000',
                        '0.00010' => '1/10000',
                        '0.00008' => '1/12500',
                        '0.00007' => '1/15000',
                        '0.00006' => '1/16000',
                        '0.00004' => '1/25000',
                        '0.00003' => '1/32000',
                        '0.00002' => '1/64000',
                        '0.00001' => '1/125000',
                    ];
                    /* --- a way to generate those "common" shutter speeds ---
                    if(empty($map)) {
                        // compute standard shutter speeds
                        for($m=1; $m<=1000; $m*=10) {
                            foreach([2, 3, 4, 5, 8, 10, 15, 16, 25, 32, 64, 125] as $n) {
                                $q = $n*$m;
                                $map[sprintf('%.5F', 1/$q)] = "1/".$q;
                            }
                        }
                        krsort($map);   // nice to copy/paste the result
                    }
                    --- */

                    // prefer "sprintf" vs "round" because we don't want things like "1E-6"
                    $value = sprintf('%.5F', $value);
                    return (array_key_exists($value, $map) ? $map[$value] : $value) . ' s.';
                },
            ],
            'flashfired_aggregate' => [
                'label' => 'FlashFired',
                'field' => 'metadata_tags.FlashFired',
                'query' => 'meta.FlashFired=%s',
                'choices' => [
                    "aggregated (2 values: fired = 0 or 1)" => -1,
                ],
                'output_formatter' => function($value) {
                    static $map = ['0'=>"No flash", '1'=>"Flash"];
                    return array_key_exists($value, $map) ? $map[$value] : $value;
                },
            ],
            'framerate_aggregate' => [
                'label' => 'FrameRate',
                'field' => 'metadata_tags.FrameRate',
                'query' => 'meta.FrameRate=%s',
            ],
            'audiosamplerate_aggregate' => [
                'label' => 'Audio Samplerate',
                'field' => 'metadata_tags.AudioSamplerate',
                'query' => 'meta.AudioSamplerate=%s',
            ],
            'videocodec_aggregate' => [
                'label' => 'Video codec',
                'field' => 'metadata_tags.VideoCodec',
                'query' => 'meta.VideoCodec:%s',
            ],
            'audiocodec_aggregate' => [
                'label' => 'Audio codec',
                'field' => 'metadata_tags.AudioCodec',
                'query' => 'meta.AudioCodec:%s',
            ],
            'orientation_aggregate' => [
                'label' => 'Orientation',
                'field' => 'metadata_tags.Orientation',
                'query' => 'meta.Orientation=%s',
            ],
            'colorspace_aggregate' => [
                'label' => 'Colorspace',
                'field' => 'metadata_tags.ColorSpace',
                'query' => 'meta.ColorSpace:%s',
            ],
            'mimetype_aggregate' => [
                'label' => 'MimeType',
                'field' => 'metadata_tags.MimeType',
                'query' => 'meta.MimeType:%s',
            ],
        ];
    }

    /**
     * @param string $order
     * @return bool returns false if order is invalid
     */
    public function setPopulateOrder($order)
    {
        $order = strtoupper($order);
        if (in_array($order, [self::POPULATE_ORDER_RID, self::POPULATE_ORDER_MODDATE])) {
            $this->populateOrder = $order;

            return true;
        }

        return false;
    }

    /**
     * @param string $direction
     * @return bool returns false if direction is invalid
     */
    public function setPopulateDirection($direction)
    {
        $direction = strtoupper($direction);
        if (in_array($direction, [self::POPULATE_DIRECTION_DESC, self::POPULATE_DIRECTION_ASC])) {
            $this->populateDirection = $direction;

            return true;
        }

        return false;
    }

    public function setAggregableFieldLimit($key, $value)
    {
        $this->_customValues[$key . '_limit'] = $value;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $ret = [
            'host'               => $this->host,
            'port'               => $this->port,
            'index'              => $this->indexName,
            'shards'             => $this->shards,
            'replicas'           => $this->replicas,
            'minScore'           => $this->minScore,
            'highlight'          => $this->highlight,
            'maxResultWindow'    => $this->maxResultWindow,
            'populate_order'     => $this->populateOrder,
            'populate_direction' => $this->populateDirection,
            'activeTab'          => $this->activeTab
        ];
        foreach (self::getAggregableTechnicalFields() as $k => $f) {
            $ret[$k . '_limit'] = $this->getAggregableFieldLimit($k);
        }

        return $ret;
    }

    public function getAggregableFieldLimit($key)
    {
        return $this->_customValues[$key . '_limit'];
    }

    /**
     * @return string
     */
    public function getPopulateOrderAsSQL()
    {
        static $orderAsColumn = [
            self::POPULATE_ORDER_RID     => "`record_id`",
            self::POPULATE_ORDER_MODDATE => "`moddate`",
        ];

        // populateOrder IS one of the keys (ensured by setPopulateOrder)
        return $orderAsColumn[$this->populateOrder];
    }

    /**
     * @return string
     */
    public function getPopulateDirectionAsSQL()
    {
        // already a SQL word
        return $this->populateDirection;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = (int)$port;
    }

    /**
     * @return int
     */
    public function getMinScore()
    {
        return $this->minScore;
    }

    /**
     * @param int $minScore
     */
    public function setMinScore($minScore)
    {
        $this->minScore = (int)$minScore;
    }

    /**
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * @param string $indexName
     */
    public function setIndexName($indexName)
    {
        $this->indexName = $indexName;
    }

    /**
     * @return int
     */
    public function getShards()
    {
        return $this->shards;
    }

    /**
     * @param int $shards
     */
    public function setShards($shards)
    {
        $this->shards = (int)$shards;
    }

    /**
     * @return int
     */
    public function getReplicas()
    {
        return $this->replicas;
    }

    /**
     * @param int $replicas
     */
    public function setReplicas($replicas)
    {
        $this->replicas = (int)$replicas;
    }

    /**
     * @return bool
     */
    public function getHighlight()
    {
        return $this->highlight;
    }

    /**
     * @param bool $highlight
     */
    public function setHighlight($highlight)
    {
        $this->highlight = $highlight;
    }

    /**
     * @return int
     */
    public function getMaxResultWindow()
    {
        return $this->maxResultWindow;
    }

    /**
     * @param int $maxResultWindow
     */
    public function setMaxResultWindow($maxResultWindow)
    {
        $this->maxResultWindow = (int)$maxResultWindow;
    }

    public function getActiveTab()
    {
        return $this->activeTab;
    }

    public function setActiveTab($activeTab)
    {
        $this->activeTab = $activeTab;
    }

    public function __get($key)
    {
        if (!array_key_exists($key, $this->_customValues)) {
            $this->_customValues[$key] = 0;
        }

        return $this->_customValues[$key];
    }

    public function __set($key, $value)
    {
        $this->_customValues[$key] = $value;
    }
}
