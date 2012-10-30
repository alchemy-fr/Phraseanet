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

use Alchemy\Phrasea\Application;

class SearchEngineOptions
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
     * Defines locale code to use for query
     *
     * @param string $locale An i18n locale code
     */
    public function setLocale($locale)
    {
        if (!preg_match('/[a-z]{2,3}/', $locale)) {
            throw new \InvalidArgumentException('Locale must be a valid i18n code');
        }

        $this->i18n = $locale;

        return $this;
    }

    /**
     * Returns the locale value
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
     * @return SearchEngineOptions
     */
    public function setSort($sort_by, $sort_ord = self::SORT_MODE_DESC)
    {
        $this->sort_by = $sort_by;
        $this->sort_ord = $sort_ord;

        return $this;
    }

    /**
     * Allows business fields query on the given collections
     * 
     * @param array $collection An array of collection
     * @return SearchEngineOptions
     */
    public function allowBusinessFieldsOn(Array $collection)
    {
        $this->business_fields = $collection;

        return $this;
    }

    /**
     * Reset business fields settings
     * 
     * @return SearchEngineOptions
     */
    public function disallowBusinessFields()
    {
        $this->business_fields = array();

        return $this;
    }

    /**
     * Returns an array of collection on which business fields are allowed to 
     * search on
     * 
     * @return array An array of collection
     */
    public function businessFieldsOn()
    {
        return $this->business_fields;
    }

    /**
     * Returns the sort criteria
     *
     * @return string
     */
    public function sortBy()
    {
        return $this->sort_by;
    }

    /**
     * Returns the sort order
     *
     * @return string
     */
    public function sortOrder()
    {
        return $this->sort_ord;
    }

    /**
     * Tells whether to use stemming or not 
     *
     * @param  boolean              $boolean
     * @return SearchEngineOptions
     */
    public function useStemming($boolean)
    {
        $this->stemming = !!$boolean;

        return $this;
    }

    /**
     * Return wheter the use of stemming is enabled or not
     *
     * @return boolean
     */
    public function stemmed()
    {
        return $this->stemming;
    }

    /**
     * Set document type to search for
     *
     * @param  int                  $search_type
     * @return SearchEngineOptions
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
     * Returns the type of documents type to search for
     *
     * @return int
     */
    public function searchType()
    {
        return $this->search_type;
    }

    /**
     * Set the collections where to search for
     * 
     * @param array $collections An array of collection
     * @return SearchEngineOptions
     */
    public function onCollections(Array $collections)
    {
        $this->collections = $collections;

        return $this;
    }

    /**
     * Returns the collections on which the search occurs
     *
     * @return array An array of collection
     */
    public function collections()
    {
        return $this->collections;
    }

    /**
     * Returns an array containing all the databoxes where the search will 
     * happen
     * 
     * @return array
     */
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
     * @return SearchEngineOptions
     */
    public function setStatus(Array $status)
    {
        $tmp = array();
        foreach ($status as $n => $options) {
            if (count($options) > 1) {
                continue;
            }
            if (isset($options['on'])) {
                foreach ($options['on'] as $sbas_id) {
                    $tmp[$n][$sbas_id] = 1;
                }
            }
            if (isset($options['off'])) {
                foreach ($options['off'] as $sbas_id) {
                    $tmp[$n][$sbas_id] = 0;
                }
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
     * @return SearchEngineOptions
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
     * @return SearchEngineOptions
     */
    public function setMinDate(\DateTime $min_date = null)
    {
        if ($min_date && $this->date_max && $min_date > $this->date_max) {
            throw new \LogicException('Min-date should be before max-date');
        }

        $this->date_min = $min_date;

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
     * @return SearchEngineOptions
     */
    public function setMaxDate(\DateTime $max_date = null)
    {
        if ($max_date && $this->date_max && $max_date < $this->date_min) {
            throw new \LogicException('Min-date should be before max-date');
        }

        $this->date_max = $max_date;

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
     * @return SearchEngineOptions
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
            if ($value instanceof \DateTime) {
                $value = $value->format(DATE_ATOM);
            }
            if (in_array($key, array('date_fields', 'fields'))) {
                $value = array_map(function(\databox_field $field) {
                            return $field->get_databox()->get_sbas_id() . '_' . $field->get_id();
                        }, $value);
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
     * @return SearchEngineOptions
     */
    public static function hydrate(Application $app, $serialized)
    {
        $serialized = json_decode($serialized, true);

        if (!is_array($serialized)) {
            throw new \InvalidArgumentException('SearchEngineOptions data are corrupted');
        }

        $options = new SearchEngineOptions();
        $options->disallowBusinessFields();

        foreach ($serialized as $key => $value) {

            switch (true) {
                case is_null($value):
                    $value = null;
                    break;
                case in_array($key, array('date_min', 'date_max')):
                    $value = \DateTime::createFromFormat(DATE_ATOM, $value);
                    break;
                case $value instanceof stdClass:
                    $tmpvalue = (array) $value;
                    $value = array();

                    foreach ($tmpvalue as $k => $data) {
                        $k = ctype_digit($k) ? (int) $k : $k;
                        $value[$k] = $data;
                    }
                    break;
                case in_array($key, array('date_fields', 'fields')):
                    $value = array_map(function($serialized) use ($app) {
                                $data = explode('_', $serialized);

                                return \databox_field::get_instance($app, $app['phraseanet.appbox']->get_databox($data[0]), $data[1]);
                                return \collection::get_from_base_id($app, $base_id);
                            }, $value);
                    break;
                case in_array($key, array('collections', 'business_fields')):
                    $value = array_map(function($base_id) use ($app) {
                                return \collection::get_from_base_id($app, $base_id);
                            }, $value);
                    break;
            }

            $sort_by = $sort_ord = null;

            switch ($key) {
                case 'record_type':
                    $options->setRecordType($value);
                    break;
                case 'search_type':
                    $options->setSearchType($value);
                    break;
                case 'status':
                    $options->setStatus($value);
                    break;
                case 'date_min':
                    $options->setMinDate($value);
                    break;
                case 'date_max':
                    $options->setMaxDate($value);
                    break;
                case 'i18n':
                    if ($value) {
                        $options->setLocale($value);
                    }
                    break;
                case 'stemming':
                    $options->useStemming($value);
                    break;
                case 'sort_by':
                    $sort_by = $value;
                    break;
                case 'sort_ord':
                    $sort_ord = $value;
                    break;
                case 'date_fields':
                    $options->setDateFields($value);
                    break;
                case 'fields':
                    $options->setFields($value);
                    break;
                case 'collections':
                    $options->onCollections($value);
                    break;
                case 'business_fields':
                    $options->allowBusinessFieldsOn($value);
                    break;
                default:
                    throw new \RuntimeException(sprintf('Unable to handle key `%s`', $key));
                    break;
            }
        }

        if ($sort_by) {
            if ($sort_ord) {
                $options->setSort($sort_by, $sort_ord);
            } else {
                $options->setSort($sort_by);
            }
        }

        return $options;
    }

}
