<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

class SearchEngineOptions implements \Serializable
{
    const RECORD_RECORD = 0;
    const RECORD_GROUPING = 1;
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_DOCUMENT = 'document';
    const TYPE_FLASH = 'flash';
    const TYPE_ALL = '';
    const SORT_RELEVANCE = 'relevance';
    const SORT_CREATED_ON = 'created_on';
    const SORT_RANDOM = 'random';
    const SORT_MODE_ASC = 'asc';
    const SORT_MODE_DESC = 'desc';

    /**
     *
     * @var string
     */
    protected $record_type;

    /**
     *
     * @var string
     */
    protected $search_type = 0;

    /**
     *
     * @var array
     */
    protected $collections = array();

    /**
     *
     * @var array
     */
    protected $fields = array();

    /**
     *
     * @var array
     */
    protected $status = array();

    /**
     *
     * @var DateTime
     */
    protected $date_min;

    /**
     *
     * @var DateTime
     */
    protected $date_max;

    /**
     *
     * @var array
     */
    protected $date_fields = array();

    /**
     *
     * @var string
     */
    protected $i18n;

    /**
     *
     * @var boolean
     */
    protected $stemming = true;

    /**
     *
     * @var string
     */
    protected $sort_by;

    /**
     *
     * @var string
     */
    protected $sort_ord = self::SORT_MODE_DESC;
    protected $business_fields = array();

    /**
     * Constructor
     *
     * @return searchEngine_options
     */
    public function __construct()
    {
        return $this;
    }

    /**
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->i18n = $locale;
    }

    /**
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->i18n;
    }

    /**
     *
     * @param  const                $sort_by
     * @param  const                $sort_ord
     * @return searchEngine_options
     */
    public function setSort($sort_by, $sort_ord = self::SORT_MODE_DESC)
    {
        $this->sort_by = $sort_by;
        $this->sort_ord = $sort_ord;

        return $this;
    }

    public function allowBusinessFieldsOn(Array $collection)
    {
        $this->business_fields = $collection;

        return $this;
    }

    public function disallowBusinessFields()
    {
        $this->business_fields = array();

        return $this;
    }

    public function businessFieldsOn()
    {
        return $this->business_fields;
    }

    /**
     *
     * @return string
     */
    public function sortBy()
    {
        return $this->sort_by;
    }

    /**
     *
     * @return string
     */
    public function sortOrder()
    {
        return $this->sort_ord;
    }

    /**
     *
     * @param  boolean              $boolean
     * @return searchEngine_options
     */
    public function useStemming($boolean)
    {
        $this->stemming = ! ! $boolean;

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function stemmed()
    {
        return $this->stemming;
    }

    /**
     *
     * @param  int                  $search_type
     * @return searchEngine_options
     */
    public function setSearchType($search_type)
    {
        switch ($search_type) {
            case self::RECORD_RECORD:
            default:
                $this->search_type = self::RECORD_RECORD;
                break;
            case self::RECORD_GROUPING:
                $this->search_type = self::RECORD_GROUPING;
                break;
        }

        return $this;
    }

    /**
     *
     * @return int
     */
    public function searchType()
    {
        return $this->search_type;
    }

    public function onCollections(Array $collections)
    {
        $this->collections = $collections;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function collections()
    {
        return $this->collections;
    }

    public function databoxes()
    {
        $databoxes = array();

        foreach ($this->collections as $collection) {
            $databoxes[$collection->get_databox()->get_sbas_id()] = $collection->get_databox();
        }

        return array_values($databoxes);
    }

    /**
     *
     * @param  array                $fields An array of Databox fields
     */
    public function setFields(Array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function fields()
    {
        return $this->fields;
    }

    /**
     *
     * @param  array                $status
     * @return searchEngine_options
     */
    public function setStatus(Array $status)
    {
        $tmp = array();
        foreach ($status as $n => $options) {
            if (count($options) > 1)
                continue;
            if (isset($options['on'])) {
                foreach ($options['on'] as $sbas_id)
                    $tmp[$n][$sbas_id] = 1;
            }
            if (isset($options['off'])) {
                foreach ($options['off'] as $sbas_id)
                    $tmp[$n][$sbas_id] = 0;
            }
        }

        $this->status = $tmp;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     *
     * @param  string               $record_type
     * @return searchEngine_options
     */
    public function setRecordType($record_type)
    {
        switch ($record_type) {
            case self::TYPE_ALL:
            default:
                $this->record_type = self::TYPE_ALL;
                break;
            case self::TYPE_AUDIO:
                $this->record_type = self::TYPE_AUDIO;
                break;
            case self::TYPE_VIDEO:
                $this->record_type = self::TYPE_VIDEO;
                break;
            case self::TYPE_DOCUMENT:
                $this->record_type = self::TYPE_DOCUMENT;
                break;
            case self::TYPE_FLASH:
                $this->record_type = self::TYPE_FLASH;
                break;
            case self::TYPE_IMAGE:
                $this->record_type = self::TYPE_IMAGE;
                break;
        }

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getRecordType()
    {
        return $this->record_type;
    }

    /**
     *
     * @param  string               $min_date
     * @return searchEngine_options
     */
    public function setMinDate($min_date)
    {
        if ( ! is_null($min_date) && trim($min_date) !== '') {
            $this->date_min = DateTime::createFromFormat('Y/m/d H:i:s', $min_date . ' 00:00:00');
        }

        return $this;
    }

    /**
     *
     * @return DateTime
     */
    public function getMinDate()
    {
        return $this->date_min;
    }

    /**
     *
     * @param  string               $max_date
     * @return searchEngine_options
     */
    public function setMaxDate($max_date)
    {
        if ( ! is_null($max_date) && trim($max_date) !== '') {
            $this->date_max = DateTime::createFromFormat('Y/m/d H:i:s', $max_date . ' 23:59:59');
        }

        return $this;
    }

    /**
     *
     * @return DateTime
     */
    public function getMaxDate()
    {
        return $this->date_max;
    }

    /**
     *
     * @param  array                $fields
     * @return searchEngine_options
     */
    public function setDateFields(Array $fields)
    {
        $this->date_fields = $fields;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getDateFields()
    {
        return $this->date_fields;
    }

    /**
     *
     * @return string
     */
    public function serialize()
    {
        $ret = array();
        foreach ($this as $key => $value) {
            if ($value instanceof DateTime) {
                $value = $value->format('d-m-Y h:i:s');
            }
            if (in_array($key, array('collections', 'business_fields'))) {
                $value = array_map(function($collection) {
                        return $collection->get_base_id();
                    }, $value);
            }

            $ret[$key] = $value;
        }

        return \p4string::jsonencode($ret);
    }

    /**
     *
     * @param  string               $serialized
     * @return searchEngine_options
     */
    public function unserialize($serialized)
    {
        $serialized = json_decode($serialized);

        foreach ($serialized as $key => $value) {
            if (is_null($value)) {
                $value = null;
            } elseif (in_array($key, array('date_min', 'date_max'))) {
                $value = new DateTime($value);
            } elseif ($value instanceof stdClass) {
                $tmpvalue = (array) $value;
                $value = array();

                foreach ($tmpvalue as $k => $data) {
                    $k = ctype_digit($k) ? (int) $k : $k;
                    $value[$k] = $data;
                }
            } elseif (in_array($key, array('collections', 'business_fields'))) {
                $value = array_map(function($base_id) {
                        return \collection::get_from_base_id($base_id);
                    }, $value);
            }

            $this->$key = $value;
        }

        return $this;
    }
}
