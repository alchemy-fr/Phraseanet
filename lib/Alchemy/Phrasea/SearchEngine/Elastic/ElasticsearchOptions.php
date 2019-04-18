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
                'type'    => 'string',
                'label'   => 'prod::facet:base_label',
                'field'   => "database",
                'esfield' => 'databox_name',
                'query'   => 'database:%s',
            ],
            'collection_aggregate' => [
                'type'    => 'string',
                'label'   => 'prod::facet:collection_label',
                'field'   => "collection",
                'esfield' => 'collection_name',
                'query'   => 'collection:%s',
            ],
            'doctype_aggregate' => [
                'type'    => 'string',
                'label'   => 'prod::facet:doctype_label',
                'field'   => "type",
                'esfield' => 'type',
                'query'   => 'type:%s',
            ],
            'camera_model_aggregate' => [
                'type'    => 'string',
                'label'   => 'Camera Model',
                'field'   => "meta.CameraModel",
                'esfield' => 'metadata_tags.CameraModel',
                'query'   => 'meta.CameraModel:%s',
            ],
            'iso_aggregate' => [
                'type'    => 'number',
                'label'   => 'ISO',
                'field'   => "meta.ISO",
                'esfield' => 'metadata_tags.ISO',
                'query'   => 'meta.ISO=%s',
            ],
            'aperture_aggregate' => [
                'type'    => 'number',
                'label'   => 'Aperture',
                'field'   => "meta.Aperture",
                'esfield' => 'metadata_tags.Aperture',
                'query'   => 'meta.Aperture=%s',
                'output_formatter' => function($value) {
                    return round($value, 1);
                },
            ],
            'shutterspeed_aggregate' => [
                'type'    => 'number',
                'label'   => 'Shutter speed',
                'field'   => "meta.ShutterSpeed",
                'esfield' => 'metadata_tags.ShutterSpeed',
                'query'   => 'meta.ShutterSpeed=%s',
                'output_formatter' => function($value) {
                    if($value < 1.0 && $value != 0) {
                        $value = '1/' . round(1.0 / $value);
                    }
                    return $value . ' s.';
                },
            ],
            'flashfired_aggregate' => [
                'type'    => 'boolean',
                'label'   => 'FlashFired',
                'field'   => "meta.FlashFired",
                'esfield' => 'metadata_tags.FlashFired',
                'query'   => 'meta.FlashFired=%s',
                'choices' => [
                    "aggregated (2 values: fired = 0 or 1)" => -1,
                ],
                'output_formatter' => function($value) {
                    static $map = ['0'=>"No flash", '1'=>"Flash"];
                    return array_key_exists($value, $map) ? $map[$value] : $value;
                },
            ],
            'framerate_aggregate' => [
                'type'    => 'number',
                'label'   => 'FrameRate',
                'field'   => "meta.FrameRate",
                'esfield' => 'metadata_tags.FrameRate',
                'query'   => 'meta.FrameRate=%s',
            ],
            'audiosamplerate_aggregate' => [
                'type'    => 'number',
                'label'   => 'Audio Samplerate',
                'field'   => "meta.AudioSamplerate",
                'esfield' => 'metadata_tags.AudioSamplerate',
                'query'   => 'meta.AudioSamplerate=%s',
            ],
            'videocodec_aggregate' => [
                'type'    => 'string',
                'label'   => 'Video codec',
                'field'   => "meta.VideoCodec",
                'esfield' => 'metadata_tags.VideoCodec',
                'query'   => 'meta.VideoCodec:%s',
            ],
            'audiocodec_aggregate' => [
                'type'    => 'string',
                'label'   => 'Audio codec',
                'field'   => "meta.AudioCodec",
                'esfield' => 'metadata_tags.AudioCodec',
                'query'   => 'meta.AudioCodec:%s',
            ],
            'orientation_aggregate' => [
                'type'    => 'string',
                'label'   => 'Orientation',
                'field'   => "meta.Orientation",
                'esfield' => 'metadata_tags.Orientation',
                'query'   => 'meta.Orientation=%s',
            ],
            'colorspace_aggregate' => [
                'type'    => 'string',
                'label'   => 'Colorspace',
                'field'   => "meta.ColorSpace",
                'esfield' => 'metadata_tags.ColorSpace',
                'query'   => 'meta.ColorSpace:%s',
            ],
            'mimetype_aggregate' => [
                'type'    => 'string',
                'label'   => 'MimeType',
                'field'   => "meta.MimeType",
                'esfield' => 'metadata_tags.MimeType',
                'query'   => 'meta.MimeType:%s',
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
