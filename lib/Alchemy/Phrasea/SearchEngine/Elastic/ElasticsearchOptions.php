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

use igorw;


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
            'host' => '127.0.0.1',
            'port' => 9200,
            'index' => '',
            'shards' => 3,
            'replicas' => 0,
            'minScore' => 4,
            'highlight' => true,
            'populate_order'     => self::POPULATE_ORDER_RID,
            'populate_direction' => self::POPULATE_DIRECTION_DESC,
            'activeTab' => null,
        ];
        $options = array_replace($defaultOptions, $options);

        $self = new self();
        $self->setHost($options['host']);
        $self->setPort($options['port']);
        $self->setIndexName($options['index']);
        $self->setShards($options['shards']);
        $self->setReplicas($options['replicas']);
        $self->setMinScore($options['minScore']);
        $self->setHighlight($options['highlight']);
        $self->setPopulateOrder($options['populate_order']);
        $self->setPopulateDirection($options['populate_direction']);
        $self->setActiveTab($options['activeTab']);
        foreach($options['aggregates'] as $fieldname=>$attributes) {
            $self->setAggregableField($fieldname, $attributes);
        }

        return $self;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $ret = [
            'host' => $this->host,
            'port' => $this->port,
            'index' => $this->indexName,
            'shards' => $this->shards,
            'replicas' => $this->replicas,
            'minScore' => $this->minScore,
            'highlight' => $this->highlight,
            'populate_order'     => $this->populateOrder,
            'populate_direction' => $this->populateDirection,
            'activeTab' => $this->activeTab,
            'aggregates' => []
        ];
        foreach($this->_customValues['aggregates'] as $fieldname=>$attributes) {
            $ret['aggregates'][$fieldname] = $attributes;
        }

        return $ret;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
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
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $minScore
     */
    public function setMinScore($minScore)
    {
        $this->minScore = (int)$minScore;
    }

    /**
     * @return int
     */
    public function getMinScore()
    {
        return $this->minScore;
    }

    /**
     * @param string $indexName
     */
    public function setIndexName($indexName)
    {
        $this->indexName = $indexName;
    }

    /**
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexName;
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
    public function getShards()
    {
        return $this->shards;
    }

    /**
     * @param int $replicas
     */
    public function setReplicas($replicas)
    {
        $this->replicas = (int)$replicas;
    }

    /**
     * @return int
     */
    public function getReplicas()
    {
        return $this->replicas;
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

    public function setAggregableField($key, $attributes)
    {
        $this->_customValues['aggregates'][$key] = $attributes;
    }

    public function getAggregableField($key)
    {
        return $this->_customValues['aggregates'][$key];
    }

    public function getAggregableFields()
    {
        return $this->_customValues['aggregates'];
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
        $keys = explode(':', $key);

        return igorw\get_in($this->_customValues, $keys);
    }

    public function __set($key, $value)
    {
        $keys = explode(':', $key);
        $this->_customValues = igorw\assoc_in($this->_customValues, $keys, $value);
    }

    public static function getAggregableTechnicalFields()
    {
        return [
            'base' => [
                'type'    => 'string',
                'label'   => 'prod::facet:base_label',
                'field'   => "database",
                'esfield' => 'databox_name',
                'query'   => 'database:%s',
            ],
            'collection' => [
                'type'    => 'string',
                'label'   => 'prod::facet:collection_label',
                'field'   => "collection",
                'esfield' => 'collection_name',
                'query'   => 'collection:%s',
            ],
            'doctype' => [
                'type'    => 'string',
                'label'   => 'prod::facet:doctype_label',
                'field'   => "type",
                'esfield' => 'type',
                'query'   => 'type:%s',
            ],
            'camera_model' => [
                'type'    => 'string',
                'label'   => 'Camera Model',
                'field'   => "meta.CameraModel",
                'esfield' => 'metadata_tags.CameraModel',
                'query'   => 'meta.CameraModel:%s',
            ],
            'iso' => [
                'type'    => 'number',
                'label'   => 'ISO',
                'field'   => "meta.ISO",
                'esfield' => 'metadata_tags.ISO',
                'query'   => 'meta.ISO=%s',
            ],
            'aperture' => [
                'type'    => 'number',
                'label'   => 'Aperture',
                'field'   => "meta.Aperture",
                'esfield' => 'metadata_tags.Aperture',
                'query'   => 'meta.Aperture=%s',
                'output_formatter' => function($value) {
                    return round($value, 1);
                },
            ],
            'shutterspeed' => [
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
            'flashfired' => [
                'type'    => 'boolean',
                'label'   => 'FlashFired',
                'field'   => "meta.FlashFired",
                'esfield' => 'metadata_tags.FlashFired',
                'query'   => 'meta.FlashFired=%s',
                'choices' => [
                    "aggregated (2 values: fired = 0 or 1)" => -1,
                ],
                'output_formatter' => function($value) {
                    static $map = ["false"=>"No flash", "true"=>"Flash", '0'=>"No flash", '1'=>"Flash"];
                    return array_key_exists($value, $map) ? $map[$value] : $value;
                },
            ],
            'framerate' => [
                'type'    => 'number',
                'label'   => 'FrameRate',
                'field'   => "meta.FrameRate",
                'esfield' => 'metadata_tags.FrameRate',
                'query'   => 'meta.FrameRate=%s',
            ],
            'audiosamplerate' => [
                'type'    => 'number',
                'label'   => 'Audio Samplerate',
                'field'   => "meta.AudioSamplerate",
                'esfield' => 'metadata_tags.AudioSamplerate',
                'query'   => 'meta.AudioSamplerate=%s',
            ],
            'videocodec' => [
                'type'    => 'string',
                'label'   => 'Video codec',
                'field'   => "meta.VideoCodec",
                'esfield' => 'metadata_tags.VideoCodec',
                'query'   => 'meta.VideoCodec:%s',
            ],
            'audiocodec' => [
                'type'    => 'string',
                'label'   => 'Audio codec',
                'field'   => "meta.AudioCodec",
                'esfield' => 'metadata_tags.AudioCodec',
                'query'   => 'meta.AudioCodec:%s',
            ],
            'orientation' => [
                'type'    => 'string',
                'label'   => 'Orientation',
                'field'   => "meta.Orientation",
                'esfield' => 'metadata_tags.Orientation',
                'query'   => 'meta.Orientation=%s',
            ],
            'colorspace' => [
                'type'    => 'string',
                'label'   => 'Colorspace',
                'field'   => "meta.ColorSpace",
                'esfield' => 'metadata_tags.ColorSpace',
                'query'   => 'meta.ColorSpace:%s',
            ],
            'mimetype' => [
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


    /**
     * @return string
     */
    public function getPopulateDirectionAsSQL()
    {
        // already a SQL word
        return $this->populateDirection;
    }

}
