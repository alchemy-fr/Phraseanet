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
class caption_record implements caption_interface, cache_cacheableInterface
{

  /**
   *
   * @var array
   */
  protected $fields;

  /**
   *
   * @var int
   */
  protected $sbas_id;

  /**
   *
   * @var record
   */
  protected $record;
  protected $dces_elements = array();
  protected $databox;

  /**
   *
   * @param record_Interface $record
   * @param databox $databox
   * @return caption_record
   */
  public function __construct(record_Interface &$record, databox &$databox)
  {
    $this->sbas_id = $record->get_sbas_id();
    $this->record = $record;
    $this->databox = $databox;


    $this->retrieve_fields();


    return $this;
  }

  protected function retrieve_fields()
  {
    if (is_array($this->fields))
    {
      return $this->fields;
    }

    $fields = array();
    try
    {
      $fields = $this->get_data_from_cache();
    }
    catch (Exception $e)
    {
      $sql  = "SELECT m.id as meta_id, s.id as structure_id
          FROM metadatas m, metadatas_structure s
          WHERE m.record_id = :record_id AND s.id = m.meta_struct_id";
      $stmt = $this->databox->get_connection()->prepare($sql);
      $stmt->execute(array(':record_id' => $this->record->get_record_id()));
      $fields      = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();
      $this->set_data_to_cache($fields);
    }

    $rec_fields = array();
    foreach ($fields as $row)
    {
      try
      {
        $databox_meta_struct = databox_field::get_instance($this->databox, $row['structure_id']);
        $metadata            = new caption_field($databox_meta_struct, $this->record, $row['meta_id']);

        $rec_fields[$databox_meta_struct->get_id()] = $metadata;
        $dces_element                               = $metadata->get_databox_field()->get_dces_element();
        if ($dces_element instanceof databox_Field_DCESAbstract)
        {
          $this->dces_elements[$dces_element->get_label()] = $databox_meta_struct->get_id();
        }
      }
      catch (Exception $e)
      {

      }
    }
    $this->fields = $rec_fields;

    return $this->fields;
  }

  /**
   *
   * @param array $grep_fields
   * @return array
   */
  public function get_fields(Array $grep_fields = null)
  {
    $fields = array();

    foreach ($this->retrieve_fields() as $meta_struct_id => $field)
    {
      if ($grep_fields && !in_array($field->get_name(), $grep_fields))
        continue;

      $fields[] = $field;
    }

    return $fields;
  }

  /**
   *
   * @param type $fieldname
   * @return \caption_field
   * @throws \Exception
   */
  public function get_field($fieldname)
  {
    foreach ($this->retrieve_fields() as $meta_struct_id => $field)
    {
      if ($field->get_name() == $fieldname)
        return $field;
    }

    throw new \Exception('Field not found');
  }

  /**
   *
   * @param type $label
   * @return caption_field
   */
  public function get_dc_field($label)
  {
    $fields = $this->retrieve_fields();
    if (isset($this->dces_elements[$label]))
    {
      return $fields[$this->dces_elements[$label]];
    }

    return null;
  }

  /**
   *
   * @param string $highlight
   * @param array $grep_fields
   * @param searchEngine_adapter $searchEngine
   * @return array
   */
  public function get_highlight_fields($highlight = '', Array $grep_fields = null, searchEngine_adapter $searchEngine = null)
  {
    return $this->highlight_fields($highlight, $grep_fields, $searchEngine);
  }

  /**
   * @todo move this fun in caption_field object
   * @param string $highlight
   * @param array $grep_fields
   * @param searchEngine_adapter $searchEngine
   * @return array
   */
  protected function highlight_fields($highlight, Array $grep_fields = null, searchEngine_adapter $searchEngine = null)
  {
    $fields = array();
    foreach ($this->fields as $meta_struct_id => $field)
    {
      if (is_array($grep_fields) && !in_array($field->get_name(), $grep_fields))
        continue;

      $value = preg_replace(
        "(([^']{1})((https?|file):((/{2,4})|(\\{2,4}))[\w:#%/;$()~_?/\-=\\\.&]*)([^']{1}))"
        , '$1 $2 <a title="' . _('Open the URL in a new window') . '" class="ui-icon ui-icon-extlink" href="$2" style="display:inline;padding:2px 5px;margin:0 4px 0 2px;" target="_blank"> &nbsp;</a>$7'
        , $field->highlight_thesaurus()
      );

      $fields[$field->get_name()] = array(
        'value'     => $value
        , 'separator' => $field->get_databox_field()->get_separator()
      );
    }

    if ($searchEngine instanceof searchEngine_adapter)
    {
      $ret = $searchEngine->build_excerpt($highlight, $fields, $this->record);

      if ($ret)
      {
        $n = 0;

        foreach ($fields as $key => $value)
        {
          $fields[$key]['value'] = $ret[$n];
          $n++;
        }
      }
    }

    return $fields;
  }

  /**
   * Part of the cache_cacheableInterface
   *
   * @param string $option
   * @return string
   */
  public function get_cache_key($option = null)
  {
    return 'caption_' . $this->record->get_serialize_key() . ($option ? '_' . $option : '');
  }

  /**
   * Part of the cache_cacheableInterface
   *
   * @param string $option
   * @return mixed
   */
  public function get_data_from_cache($option = null)
  {
    $databox = databox::get_instance($this->record->get_sbas_id());

    return $databox->get_data_from_cache($this->get_cache_key($option));
  }

  /**
   * Part of the cache_cacheableInterface
   *
   * @param mixed $value
   * @param string $option
   * @param int $duration
   * @return caption_field
   */
  public function set_data_to_cache($value, $option = null, $duration = 0)
  {
    $databox = databox::get_instance($this->record->get_sbas_id());

    return $databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
  }

  /**
   * Part of the cache_cacheableInterface
   *
   * @param string $option
   * @return caption_field
   */
  public function delete_data_from_cache($option = null)
  {
    $databox = databox::get_instance($this->record->get_sbas_id());
    $this->fields = null;

    return $databox->delete_data_from_cache($this->get_cache_key($option));
  }

}
