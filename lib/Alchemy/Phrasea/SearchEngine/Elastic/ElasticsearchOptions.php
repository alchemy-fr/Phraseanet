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

use databox_field;
use igorw;
use Symfony\Component\Translation\TranslatorInterface;


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

    /** @var  int[][] */
    private $_customValues = [];
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
            'host' => 'elasticsearch',
            'port' => 9200,
            'index' => '',
            'shards' => 3,
            'replicas' => 0,
            'minScore' => 4,
            'highlight' => true,
            'maxResultWindow'    => 500000,
            'populate_order'     => self::POPULATE_ORDER_RID,
            'populate_direction' => self::POPULATE_DIRECTION_DESC,
            'activeTab' => null,
            'facets' => []
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
        $self->setMaxResultWindow($options['maxResultWindow']);
        $self->setPopulateOrder($options['populate_order']);
        $self->setPopulateDirection($options['populate_direction']);
        $self->setActiveTab($options['activeTab']);
        foreach($options['facets'] as $fieldname=>$attributes) {
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
            'maxResultWindow'    => $this->maxResultWindow,
            'populate_order'     => $this->populateOrder,
            'populate_direction' => $this->populateDirection,
            'activeTab' => $this->activeTab,
            'facets' => []
        ];
        foreach($this->getAggregableFields() as $fieldname=>$attributes) {
            $ret['facets'][$fieldname] = $attributes;
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

    public function setAggregableFieldLimit($key, $value)
    {
        if(is_null($this->getAggregableField($key))) {
            $this->_customValues['facets'][$key] = [];
        }
        $this->_customValues['facets'][$key]['limit'] = $value;
    }

    public function setAggregableField($key, $attributes)
    {
        $this->getAggregableFields();    // ensure facets exists
        $this->_customValues['facets'][$key] = $attributes;
    }

    public function getAggregableFieldLimit($key)
    {
        $facet = $this->getAggregableField($key);
        return (is_array($facet) && array_key_exists('limit', $facet)) ? $facet['limit'] : databox_field::FACET_DISABLED;
    }

    public function getAggregableField($key)
    {
        $facets = $this->getAggregableFields();
        return array_key_exists($key, $facets) ? $facets[$key] : null;
    }

    /**
     * @return array
     */
    public function getAggregableFields()
    {
        if(!array_key_exists('facets', $this->_customValues) || !is_array($this->_customValues['facets'])) {
            $this->_customValues['facets'] = [];
        }
        return $this->_customValues['facets'];
    }

    // set to change the facets order during admin/form save
    public function reorderAggregableFields($facetNames)
    {
        $newFacets = [];
        foreach ($facetNames as $name) {
            if(($facet = $this->getAggregableField($name)) !== null) {
                $newFacets[$name] = $facet;
            }
        }
        $this->_customValues['facets'] = $newFacets;
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

    public static function getAggregableTechnicalFields(TranslatorInterface $translator)
    {
        return [
            '_base' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet:base_label'),
                'field'   => "database",
                'esfield' => 'databox_name',
                'query'   => 'database=%s',
            ],
            '_collection' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet:collection_label'),
                'field'   => "collection",
                'esfield' => 'collection_name',
                'query'   => 'collection=%s',
            ],
            '_doctype' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet:doctype_label'),
                'field'   => "type",
                'esfield' => 'type',
                'query'   => 'type=%s',
            ],
            '_camera_model' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet:CameraModel_label'),
                'field'   => "meta.CameraModel",
                'esfield' => 'metadata_tags.CameraModel',
                'query'   => 'meta.CameraModel=%s',
            ],
            '_iso' => [
                'type'    => 'number',
                'label'   => $translator->trans('prod::facet:ISO_label'),
                'field'   => "meta.ISO",
                'esfield' => 'metadata_tags.ISO',
                'query'   => 'meta.ISO=%s',
            ],
            '_aperture' => [
                'type'    => 'number',
                'label'   => $translator->trans('prod::facet:Aperture_label'),
                'field'   => "meta.Aperture",
                'esfield' => 'metadata_tags.Aperture',
                'query'   => 'meta.Aperture=%s',
                'output_formatter' => function($value) {
                    return round($value, 1);
                },
            ],
            '_shutterspeed' => [
                'type'    => 'number',
                'label'   => $translator->trans('prod::facet:ShutterSpeed_label'),
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
            '_flashfired' => [
                'type'    => 'boolean',
                'label'   => $translator->trans('prod::facet:FlashFired_label'),
                'field'   => "meta.FlashFired",
                'esfield' => 'metadata_tags.FlashFired',
                'query'   => 'meta.FlashFired=%s',
                'choices' => [
                    "aggregated (2 values: fired = 0 or 1)" => -1,
                ],
                'output_formatter' => function($value) use($translator) {
                    $map = [
                        "false" => $translator->trans("facet.flashfired:no"),
                        "true" => $translator->trans("facet.flashfired:yes"),
                        '0' => $translator->trans("facet.flashfired:no"),
                        '1' => $translator->trans("facet.flashfired:yes")
                    ];
                    return array_key_exists($value, $map) ? $map[$value] : $value;
                },
            ],
            '_framerate' => [
                'type'    => 'number',
                'label'   => $translator->trans('prod::facet:FrameRate_label'),
                'field'   => "meta.FrameRate",
                'esfield' => 'metadata_tags.FrameRate',
                'query'   => 'meta.FrameRate=%s',
            ],
            '_audiosamplerate' => [
                'type'    => 'number',
                'label'   => $translator->trans('prod::facet:AudioSamplerate_label'),
                'field'   => "meta.AudioSamplerate",
                'esfield' => 'metadata_tags.AudioSamplerate',
                'query'   => 'meta.AudioSamplerate=%s',
            ],
            '_videocodec' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet:VideoCodec_label'),
                'field'   => "meta.VideoCodec",
                'esfield' => 'metadata_tags.VideoCodec',
                'query'   => 'meta.VideoCodec=%s',
            ],
            '_audiocodec' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet:AudioCodec_label'),
                'field'   => "meta.AudioCodec",
                'esfield' => 'metadata_tags.AudioCodec',
                'query'   => 'meta.AudioCodec=%s',
            ],
            '_orientation' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet.Orientation_label'),
                'field'   => "meta.Orientation",
                'esfield' => 'metadata_tags.Orientation',
                'query'   => 'meta.Orientation=%s',
            ],
            '_thumbnail_orientation' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet.ThumbnailOrientation_label'),
                'field'   => "meta.ThumbnailOrientation",
                'esfield' => 'metadata_tags.ThumbnailOrientation',
                'query'   => 'meta.ThumbnailOrientation=%s',
                'choices' => [
                    "aggregated (4 values: '', 'S', 'L', 'P')" => -1,
                ],
                'output_formatter' => function($value) use($translator) {
                    $map = [
                        "L" => $translator->trans("facet.ThumbnailOrientation:Landscape"),
                        "P" => $translator->trans("facet.ThumbnailOrientation:Portrait"),
                        'S' => $translator->trans("facet.ThumbnailOrientation:Square")
                    ];
                    return array_key_exists($value, $map) ? $map[$value] : $value;
                },
            ],
            '_colorspace' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet:Colorspace_label'),
                'field'   => "meta.ColorSpace",
                'esfield' => 'metadata_tags.ColorSpace',
                'query'   => 'meta.ColorSpace=%s',
            ],
            '_mimetype' => [
                'type'    => 'string',
                'label'   => $translator->trans('prod::facet:MimeType_label'),
                'field'   => "meta.MimeType",
                'esfield' => 'metadata_tags.MimeType',
                'query'   => 'meta.MimeType=%s',
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
    public function getPopulateOrder()
    {
        return $this->populateOrder;
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
    public function getPopulateDirection()
    {
        return $this->populateDirection;
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
