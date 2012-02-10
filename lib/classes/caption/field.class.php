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
class caption_field
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
    $this->databox_field = $databox_field;
    $this->values = array();

    $connbas = $databox_field->get_connection();

    $sql = 'SELECT id FROM metadatas
                WHERE record_id = :record_id
                  AND meta_struct_id = :meta_struct_id';

    $params = array(
      ':record_id'      => $record->get_record_id()
      , ':meta_struct_id' => $databox_field->get_id()
    );

    $stmt = $connbas->prepare($sql);
    $stmt->execute($params);
    $rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

  /**
   *
   * @return boolean
   */
  public function is_required()
  {
    return $this->databox_field->is_required();
  }

  /**
   *
   * @return boolean
   */
  public function is_multi()
  {
    return $this->databox_field->is_multi();
  }

  /**
   *
   * @return boolean
   */
  public function is_readonly()
  {
    return $this->databox_field->is_readonly();
  }

  /**
   *
   * @return caption_field
   */
  public function delete()
  {

    foreach ($this->get_values() as $value)
    {
      $value->delete();
    }

    return $this;
  }

  /**
   *
   * @param array $values
   * @param string $separator
   * @return string
   */
  protected static function serialize_value(Array $values, $separator)
  {
    if (strlen($separator) > 1)
      $separator = $separator[0];

    if (trim($separator) === '')
      $separator = ' ';
    else
      $separator = ' ' . $separator . ' ';

    $array_values = array();

    foreach ($values as $value)
    {
      $array_values[] = $value->getValue();
    }

    return implode($separator, $array_values);
  }

  /**
   *
   * @return array
   */
  public function get_values()
  {
    return $this->values;
  }

  /**
   *
   * @param int $meta_id
   * @return array
   */
  public function get_value($meta_id)
  {
    return $this->values[$meta_id];
  }

  /**
   *
   * @param string $custom_separator
   * @return mixed
   */
  public function get_serialized_values($custom_separator = false)
  {
    if ($this->databox_field->is_multi() === true)
    {
      if ($custom_separator !== false)
        $separator = $custom_separator;
      else
        $separator = $this->databox_field->get_separator();

      return self::serialize_value($this->values, $separator);
    }
    else
    {
      foreach ($this->values as $value)
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
    $appbox   = appbox::get_instance();
    $session  = $appbox->get_session();
    $registry = $appbox->get_registry();
    $unicode  = new unicode();

    $sbas_id = $this->databox_field->get_databox()->get_sbas_id();

    $value = $this->get_serialized_values();

    $databox         = databox::get_instance($sbas_id);
    $XPATH_thesaurus = $databox->get_xpath_thesaurus();

    $tbranch = $this->databox_field->get_tbranch();
    if ($tbranch && $XPATH_thesaurus)
    {
      $DOM_branchs = $XPATH_thesaurus->query($tbranch);

      $fvalue = $value;

      $cleanvalue = str_replace(array("<em>", "</em>", "'"), array("", "", "&apos;"), $fvalue);

      list($term_noacc, $context_noacc) = $this->splitTermAndContext($cleanvalue);
      $term_noacc    = $unicode->remove_indexer_chars($term_noacc);
      $context_noacc = $unicode->remove_indexer_chars($context_noacc);
      if ($context_noacc)
      {
        $q = "//sy[@w='" . $term_noacc . "' and @k='" . $context_noacc . "']";
      }
      else
      {
        $q    = "//sy[@w='" . $term_noacc . "' and not(@k)]";
      }
      $qjs  = $link = "";
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
                $link = $qjs  = $synonym->getAttribute("v");
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
    $term    = trim($word);
    $context = "";
    if (($po      = strpos($term, "(")) !== false)
    {
      if (($pc = strpos($term, ")", $po)) !== false)
      {
        $context = trim(substr($term, $po + 1, $pc - $po - 1));
        $term    = trim(substr($term, 0, $po));
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
      $values    = preg_split($separator, $serialized_value);
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

    $stmt   = $databox_field->get_databox()->get_connection()->prepare($sql);
    $params = array(
      ':meta_struct_id' => $databox_field->get_id()
    );

    $stmt->execute($params);
    $rowcount = $stmt->rowCount();
    $stmt->closeCursor();

    $n         = 0;
    $increment = 500;

    while ($n < $rowcount)
    {
      $sql = 'SELECT record_id, id FROM metadatas
              WHERE meta_struct_id = :meta_struct_id
              LIMIT ' . $n . ', ' . $increment;

      $params = array(
        ':meta_struct_id' => $databox_field->get_id()
      );

      $stmt     = $databox_field->get_databox()->get_connection()->prepare($sql);
      $stmt->execute($params);
      $rowcount = $stmt->rowCount();
      $rs       = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();
      unset($stmt);

      foreach ($rs as $row)
      {
        try
        {
          $record        = $databox_field->get_databox()->get_record($row['record_id']);
          $caption_field = new caption_field($databox_field, $record);
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
