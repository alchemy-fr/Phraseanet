<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     searchEngine
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class searchEngine_options implements Serializable
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
  protected $bases = array();

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
  protected $sort_by = self::SORT_CREATED_ON;

  /**
   *
   * @var string
   */
  protected $sort_ord = self::SORT_MODE_DESC;

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
  public function set_locale($locale)
  {
    $this->i18n = $locale;
  }

  /**
   *
   * @return string
   */
  public function get_locale()
  {
    return $this->i18n;
  }

  /**
   *
   * @param const $sort_by
   * @param const $sort_ord
   * @return searchEngine_options
   */
  public function set_sort($sort_by, $sort_ord = self::SORT_MODE_DESC)
  {
    $this->sort_by = $sort_by;
    $this->sort_ord = $sort_ord;

    return $this;
  }

  /**
   *
   * @return string
   */
  public function get_sortby()
  {
    return $this->sort_by;
  }

  /**
   *
   * @return string
   */
  public function get_sortord()
  {
    return $this->sort_ord;
  }

  /**
   *
   * @param boolean $boolean
   * @return searchEngine_options
   */
  public function set_use_stemming($boolean)
  {
    $this->stemming = !!$boolean;

    return $this;
  }

  /**
   *
   * @return boolean
   */
  public function get_use_stemming()
  {
    return $this->stemming;
  }

  /**
   *
   * @param int $search_type
   * @return searchEngine_options
   */
  public function set_search_type($search_type)
  {
    switch ($search_type)
    {
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
  public function get_search_type()
  {
    return $this->search_type;
  }

  /**
   *
   * @param array $base_ids
   * @param ACL $ACL
   * @return searchEngine_options
   */
  public function set_bases(Array $base_ids, ACL $ACL)
  {
    foreach ($base_ids as $base_id)
    {
      if ($ACL->has_access_to_base($base_id))
        $this->bases[$base_id] = $base_id;
    }

    return $this;
  }

  /**
   *
   * @return array
   */
  public function get_bases()
  {
    return $this->bases;
  }

  /**
   *
   * @param array $fields
   * @return searchEngine_options
   */
  public function set_fields(Array $fields)
  {
    $this->fields = $fields;

    return $this;
  }

  /**
   *
   * @return array
   */
  public function get_fields()
  {
    return $this->fields;
  }

  /**
   *
   * @param array $status
   * @return searchEngine_options
   */
  public function set_status(Array $status)
  {
    $tmp = array();
    foreach ($status as $n => $options)
    {
      if (count($options) > 1)
        continue;
      if (isset($options['on']))
      {
        foreach ($options['on'] as $sbas_id)
          $tmp[$n][$sbas_id] = 1;
      }
      if (isset($options['off']))
      {
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
  public function get_status()
  {
    return $this->status;
  }

  /**
   *
   * @param string $record_type
   * @return searchEngine_options
   */
  public function set_record_type($record_type)
  {
    switch ($record_type)
    {
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
  public function get_record_type()
  {
    return $this->record_type;
  }

  /**
   *
   * @param string $min_date
   * @return searchEngine_options
   */
  public function set_min_date($min_date)
  {
    if (!is_null($min_date) && trim($min_date) !== '')
    {
      $this->date_min = DateTime::createFromFormat('d/m/Y h:i:s', $min_date.' 00:00:00');
    }

    return $this;
  }

  /**
   *
   * @return DateTime
   */
  public function get_min_date()
  {
    return $this->date_min;
  }

  /**
   *
   * @param string $max_date
   * @return searchEngine_options
   */
  public function set_max_date($max_date)
  {
    if (!is_null($max_date) && trim($max_date) !== '')
    {
      $this->date_max = DateTime::createFromFormat('d/m/Y h:i:s', $max_date.' 23:59:59');
    }

    return $this;
  }

  /**
   *
   * @return DateTime
   */
  public function get_max_date()
  {
    return $this->date_max;
  }

  /**
   *
   * @param array $fields
   * @return searchEngine_options
   */
  public function set_date_fields(Array $fields)
  {
    $this->date_fields = $fields;

    return $this;
  }

  /**
   *
   * @return array
   */
  public function get_date_fields()
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
    foreach ($this as $key => $value)
    {
      if ($value instanceof DateTime)
        $value = $value->format('d-m-Y h:i:s');

      $ret[$key] = $value;
    }

    return p4string::jsonencode($ret);
  }

  /**
   *
   * @param string $serialized
   * @return searchEngine_options
   */
  public function unserialize($serialized)
  {
    $serialized = json_decode($serialized);

    foreach ($serialized as $key => $value)
    {
      if(is_null($value))
      {
        $value = null;
      }
      elseif (in_array($key, array('date_min', 'date_max')))
      {
        $value = new DateTime($value);
      }
      elseif ($value instanceof stdClass)
      {
        $tmpvalue = (array) $value;
        $value = array();
        
        foreach($tmpvalue as $k=>$data)
        {
          $k = ctype_digit($k) ? (int) $k : $k;
          $value[$k] = $data;
        }
        
      }

      $this->$key = $value;
    }

    return $this;
  }

}
