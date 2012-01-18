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
 * @package     caption
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class caption_field //implements cache_cacheableInterface
{

  /**
   *
   * @var databox_field
   */
  protected $databox_field;

  /**
   *
   * @var string
   */
  protected $values;

//  /**
//   *
//   * @var int
//   */
//  protected $id;

  /**
   *
   * @var record
   */
  protected $record;

  /**
   *
   * @param databox_field $databox_field
   * @param record_Interface $record
   * @param int $id
   * @return caption_field
   */
  public function __construct(databox_field &$databox_field, record_Interface $record)
  {
    $this->record = $record;
//    $this->id = (int) $id;
    $this->databox_field = $databox_field;
    $this->values = array();

    $connbas = $databox_field->get_connection();

    $sql = 'SELECT id FROM metadatas 
                WHERE record_id = :record_id 
                  AND meta_struct_id = :meta_struct_id';

    $params = array(
        ':record_id' => $record->get_record_id()
        , ':meta_struct_id' => $databox_field->get_id()
    );

    $stmt = $connbas->prepare($sql);
    $stmt->execute($params);
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$databox_field->is_multi() && count($rs) > 1)
    {
      /**
       * TRIGG CORRECTION; 
       */
    }

    foreach ($rs as $row)
    {
      $this->values[$row['id']] = new caption_Field_Value($databox_field, $record, $row['id']);
    }

    return $this;
  }
  
  /**
   *
   * @return record_adapter 
   */
  public function get_record()
  {
    return $this->record;
  }

  public function is_required()
  {
    return $this->databox_field->is_required();
  }

  public function is_multi()
  {
    return $this->databox_field->is_multi();
  }

  public function is_readonly()
  {
    return $this->databox_field->is_readonly();
  }

//  /**
//   *
//   * @return caption_field
//   */
//  public function delete()
//  {
//    $connbas = $this->databox_field->get_connection();
//
//    $sql = 'DELETE FROM metadatas WHERE id = :id';
//    $stmt = $connbas->prepare($sql);
//    $stmt->execute(array(':id' => $this->id));
//    $stmt->closeCursor();
//    $this->delete_data_from_cache();
//
//    $sbas_id = $this->record->get_sbas_id();
//    $this->record->get_caption()->delete_data_from_cache();
//
//    try
//    {
//      $registry = registry::get_instance();
//      $sphinx_rt = sphinxrt::get_instance($registry);
//
//      $sbas_params = phrasea::sbas_params();
//
//      if (isset($sbas_params[$sbas_id]))
//      {
//        $params = $sbas_params[$sbas_id];
//        $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
//        $sphinx_rt->delete(array("metadatas" . $sbas_crc, "metadatas" . $sbas_crc . "_stemmed_fr", "metadatas" . $sbas_crc . "_stemmed_en"), "metas_realtime" . $sbas_crc, $this->id);
//        $sphinx_rt->delete(array("documents" . $sbas_crc, "documents" . $sbas_crc . "_stemmed_fr", "documents" . $sbas_crc . "_stemmed_en"), "docs_realtime" . $sbas_crc, $this->record->get_record_id());
//      }
//    }
//    catch (Exception $e)
//    {
//      unset($e);
//    }
//
//    return $this;
//  }
//
//  /**
//   * Part of the cache_cacheableInterface
//   *
//   * @param string $option
//   * @return string
//   */
//  public function get_cache_key($option = null)
//  {
//    return 'captionfield_' . $this->record->get_serialize_key()
//            . $this->id . ($option ? '_' . $option : '');
//  }
//
//  /**
//   * Part of the cache_cacheableInterface
//   *
//   * @param string $option
//   * @return mixed
//   */
//  public function get_data_from_cache($option = null)
//  {
//    $databox = databox::get_instance($this->record->get_sbas_id());
//
//    return $databox->get_data_from_cache($this->get_cache_key($option));
//  }
//
//  /**
//   * Part of the cache_cacheableInterface
//   *
//   * @param mixed $value
//   * @param string $option
//   * @param int $duration
//   * @return caption_field
//   */
//  public function set_data_to_cache($value, $option = null, $duration = 0)
//  {
//    $databox = databox::get_instance($this->record->get_sbas_id());
//    $databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
//
//    return $this;
//  }
//
//  /**
//   * Part of the cache_cacheableInterface
//   *
//   * @param string $option
//   * @return caption_field
//   */
//  public function delete_data_from_cache($option = null)
//  {
//    $databox = databox::get_instance($this->record->get_sbas_id());
//    $databox->delete_data_from_cache($this->get_cache_key($option));
//
//    return $this;
//  }
//
//  /**
//   *
//   * @param array $value
//   * @param databox_field $databox_field
//   * @return string
//   */
  protected static function serialize_value(Array $values, $separator)
  {
    if (strlen($separator) > 1)
      $separator = $separator[0];

    if (trim($separator) === '')
      $separator = ' ';
    else
      $separator = ' ' . $separator . ' ';
    
    $array_values = array();
    
    foreach($values as $value)
    {
      $array_values[] = $value->get_value();
    }

    return implode($separator, $array_values);
  }
//
//  /**
//   *
//   * @param array $value
//   * @return caption_field
//   */
//  public function set_value(Array $value)
//  {
//    $sbas_id = $this->databox_field->get_databox()->get_sbas_id();
//    $connbas = $this->databox_field->get_connection();
//
//    $sql_up = 'UPDATE metadatas SET value = :value WHERE id = :meta_id';
//    $stmt_up = $connbas->prepare($sql_up);
//    $stmt_up->execute(array(':meta_id' => $this->get_meta_id(), ':value' => self::serialize_value($value, $this->databox_field->get_separator())));
//    $stmt_up->closeCursor();
//
//    try
//    {
//      $registry = registry::get_instance();
//      $sphinx_rt = sphinxrt::get_instance($registry);
//
//      $sbas_params = phrasea::sbas_params();
//
//      if (isset($sbas_params[$sbas_id]))
//      {
//        $params = $sbas_params[$sbas_id];
//        $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
//        $sphinx_rt->delete(array("metadatas" . $sbas_crc, "metadatas" . $sbas_crc . "_stemmed_fr", "metadatas" . $sbas_crc . "_stemmed_en"), "", $this->get_meta_id());
//        $sphinx_rt->delete(array("documents" . $sbas_crc, "documents" . $sbas_crc . "_stemmed_fr", "documents" . $sbas_crc . "_stemmed_en"), "", $this->record->get_record_id());
//      }
//    }
//    catch (Exception $e)
//    {
//      
//    }
//
//    $this->update_cache_value($value);
//
//    return $this;
//  }
//
//  /**
//   *
//   * @param array $value
//   * @return caption_field
//   */
//  public function update_cache_value(Array $value)
//  {
//    $this->delete_data_from_cache();
//    $this->record->get_caption()->delete_data_from_cache();
//    $sbas_id = $this->databox_field->get_databox()->get_sbas_id();
//    try
//    {
//      $registry = registry::get_instance();
//
//      $sbas_params = phrasea::sbas_params();
//
//      if (isset($sbas_params[$sbas_id]))
//      {
//        $params = $sbas_params[$sbas_id];
//        $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
//
//        $sphinx_rt = sphinxrt::get_instance($registry);
//        $sphinx_rt->replace_in_metas(
//                "metas_realtime" . $sbas_crc, $this->id, $this->databox_field->get_id(), $this->record->get_record_id(), $sbas_id, phrasea::collFromBas($this->record->get_base_id()), ($this->record->is_grouping() ? '1' : '0'), $this->record->get_type(), $value, $this->record->get_creation_date()
//        );
//
//        $all_datas = array();
//        foreach ($this->record->get_caption()->get_fields() as $field)
//        {
//          if (!$field->is_indexable())
//            continue;
//          $all_datas[] = $field->get_value(true);
//        }
//        $all_datas = implode(' ', $all_datas);
//
//        $sphinx_rt->replace_in_documents(
//                "docs_realtime" . $sbas_crc, //$this->id,
//                $this->record->get_record_id(), $all_datas, $sbas_id, phrasea::collFromBas($this->record->get_base_id()), ($this->record->is_grouping() ? '1' : '0'), $this->record->get_type(), $this->record->get_creation_date()
//        );
//      }
//    }
//    catch (Exception $e)
//    {
//      unset($e);
//    }
//
//    return $this;
//  }

  /**
   *
   * @param databox_field $databox_field
   * @param record_Interface $record
   * @param array $value
   * @return caption_field
//   */
//  public static function create(databox_field &$databox_field, record_Interface $record, Array $value)
//  {
//
//    $sbas_id = $databox_field->get_databox()->get_sbas_id();
//    $connbas = $databox_field->get_connection();
//    $sql_ins = 'INSERT INTO metadatas (id, record_id, meta_struct_id, value)
//            VALUES
//            (null, :record_id, :field, :value)';
//    $stmt_ins = $connbas->prepare($sql_ins);
//    $stmt_ins->execute(
//            array(
//                ':record_id' => $record->get_record_id(),
//                ':field' => $databox_field->get_id(),
//                ':value' => self::serialize_value($value, $databox_field->get_separator())
//            )
//    );
//    $stmt_ins->closeCursor();
//    $meta_id = $connbas->lastInsertId();
//
//    $caption_field = new self($databox_field, $record, $meta_id);
//    $caption_field->update_cache_value($value);
//
//    $record->get_caption()->delete_data_from_cache();
//
//    return $caption_field;
//  }

//  /**
//   *
//   * @return string
//   */
//  public function get_value($as_string = false, $custom_separator = false)
//  {
//    if ($this->databox_field->is_multi() === true)
//    {
//      if ($as_string === true && $custom_separator === false)
//      {
//        return $this->value;
//      }
//      $separator = $this->databox_field->get_separator();
//      $array_values = self::get_multi_values($this->value, $separator);
//
//      if ($as_string === true && $custom_separator !== false)
//        return self::serialize_value($array_values, $custom_separator);
//      else
//        return $array_values;
//    }
//    else
//    {
//      return $this->value;
//    }
//  }
  
  public function get_values()
  {
    return $this->values;
  }
  
  public function get_value($meta_id)
  {
    return $this->values[$meta_id];
  }
  
  public function get_serialized_values($custom_separator = false)
  {
    if ($this->databox_field->is_multi() === true)
    {
      if($custom_separator !== false)
        $separator = $custom_separator;
      else
        $separator = $this->databox_field->get_separator();

      return self::serialize_value($this->values, $separator);
    }
    else
    {
      foreach($this->values as $value)
      {
        /* @var $value Caption_Field_Value */
        return $value->getValue();
      }
    }
    
    return null;
  }

  /**
   *
   * @return string
   */
  public function get_name()
  {
    return $this->databox_field->get_name();
  }

  /**
   *
   * @return int
   */
  public function get_meta_struct_id()
  {
    return $this->databox_field->get_id();
  }

  /**
   *
   * @return boolean
   */
  public function is_indexable()
  {
    return $this->databox_field->is_indexable();
  }

//  /**
//   *
//   * @return int
//   */
//  public function get_meta_id()
//  {
//    return $this->id;
//  }

  /**
   *
   * @return databox_field
   */
  public function get_databox_field()
  {
    return $this->databox_field;
  }

  /**
   *
   * @return string
   */
  public function highlight_thesaurus()
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $registry = $appbox->get_registry();
    $unicode = new unicode();

    $sbas_id = $this->databox_field->get_databox()->get_sbas_id();

    $value = $this->get_serialized_values();

    $databox = databox::get_instance($sbas_id);
    $XPATH_thesaurus = $databox->get_xpath_thesaurus();

    $tbranch = $this->databox_field->get_tbranch();
    if ($tbranch && $XPATH_thesaurus)
    {
      $DOM_branchs = $XPATH_thesaurus->query($tbranch);

      $fvalue = $value;

      $cleanvalue = str_replace(array("<em>", "</em>", "'"), array("", "", "&apos;"), $fvalue);

      list($term_noacc, $context_noacc) = $this->splitTermAndContext($cleanvalue);
      $term_noacc = $unicode->remove_indexer_chars($term_noacc);
      $context_noacc = $unicode->remove_indexer_chars($context_noacc);
      if ($context_noacc)
      {
        $q = "//sy[@w='" . $term_noacc . "' and @k='" . $context_noacc . "']";
      }
      else
      {
        $q = "//sy[@w='" . $term_noacc . "' and not(@k)]";
      }
      $qjs = $link = "";
      foreach ($DOM_branchs as $DOM_branch)
      {
        $nodes = $XPATH_thesaurus->cache_query($q, $DOM_branch);
        if ($nodes->length > 0)
        {
          $lngfound = false;
          foreach ($nodes as $node)
          {
            if ($node->getAttribute("lng") == $session->get_I18n())
            {
              // le terme est dans la bonne langue, on le rend cliquable
              list($term, $context) = $this->splitTermAndContext($fvalue);
              $term = str_replace(array("<em>", "</em>"), array("", ""), $term);
              $context = str_replace(array("<em>", "</em>"), array("", ""), $context);
              $qjs = $term;
              if ($context)
              {
                $qjs .= " [" . $context . "]";
              }
              $link = $fvalue;

              $lngfound = true;
              break;
            }

            $synonyms = $XPATH_thesaurus->query("sy[@lng='" . $session->usr_i18 . "']", $node->parentNode);
            foreach ($synonyms as $synonym)
            {
              $k = $synonym->getAttribute("k");
              if ($synonym->getAttribute("w") != $term_noacc || $k != $context_noacc)
              {
                $link = $qjs = $synonym->getAttribute("v");
                if ($k)
                {
                  $link .= " (" . $k . ")";
                  $qjs .= " [" . $k . "]";
                }

                $lngfound = true;
                break;
              }
            }
          }
          if (!$lngfound)
          {
            list($term, $context) = $this->splitTermAndContext($fvalue);
            $term = str_replace(array("<em>", "</em>"), array("", ""), $term);
            $context = str_replace(array("<em>", "</em>"), array("", ""), $context);
            $qjs = $term;
            if ($context)
            {
              $qjs .= " [" . $context . "]";
            }
            $link = $fvalue;
          }
        }
      }
      if ($qjs)
      {
        $value = "<a class=\"bounce\" onclick=\"bounce('" . $sbas_id . "','"
                . str_replace("'", "\'", $qjs)
                . "', '"
                . str_replace("'", "\'", $this->get_name())
                . "');return(false);\">"
                . $link
                . "</a>";
      }
    }

    return $value;
  }

  /**
   *
   * @param string $word
   * @return array
   */
  protected function splitTermAndContext($word)
  {
    $term = trim($word);
    $context = "";
    if (($po = strpos($term, "(")) !== false)
    {
      if (($pc = strpos($term, ")", $po)) !== false)
      {
        $context = trim(substr($term, $po + 1, $pc - $po - 1));
        $term = trim(substr($term, 0, $po));
      }
    }

    return array($term, $context);
  }

  /**
   *
   * @param string $serialized_value
   * @param string $separator
   * @return array
   */
  public static function get_multi_values($serialized_value, $separator)
  {
    $values = array();
    if (strlen($separator) == 1)
    {
      $values = explode($separator, $serialized_value);
    }
    else
    {
      // s'il y'a plusieurs delimiters, on transforme
      // en regexp pour utiliser split
      $separator = preg_split('//', $separator, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
      $separator = '/\\' . implode('|\\', $separator) . '/';
      $values = preg_split($separator, $serialized_value);
    }

    foreach ($values as $key => $value)
    {
      $values[$key] = trim($value);
    }

    return $values;
  }

  public static function delete_all_metadatas(databox_field $databox_field)
  {
    $sql = 'SELECT count(id) as count_id FROM metadatas
            WHERE meta_struct_id = :meta_struct_id';
    $stmt = $databox_field->get_databox()->get_connection()->prepare($sql);
    $params = array(
        ':meta_struct_id' => $databox_field->get_id()
    );

    $stmt->execute($params);
    $rowcount = $stmt->rowCount();
    $stmt->closeCursor();

    $n = 0;
    $increment = 500;

    while ($n < $rowcount)
    {
      $sql = 'SELECT record_id, id FROM metadatas
              WHERE meta_struct_id = :meta_struct_id LIMIT ' . $n . ', ' . $increment;

      $params = array(
          ':meta_struct_id' => $databox_field->get_id()
      );

      $stmt = $databox_field->get_databox()->get_connection()->prepare($sql);
      $stmt->execute($params);
      $rowcount = $stmt->rowCount();
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();
      unset($stmt);

      foreach ($rs as $row)
      {
        try
        {
          $record = $databox_field->get_databox()->get_record($row['record_id']);
          $caption_field = new caption_field($databox_field, $record, $row['id']);
          $caption_field->delete();
          unset($caption_field);
          unset($record);
        }
        catch (Exception $e)
        {
          
        }
      }

      $n += $increment;
    }

    return;
  }

}
