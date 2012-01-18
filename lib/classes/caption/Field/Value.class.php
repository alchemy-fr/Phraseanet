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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class caption_Field_Value
{

  /**
   *
   * @var int 
   */
  protected $id;
  /**
   *
   * @var string 
   */
  protected $value;
  /**
   *
   * @var databox_field 
   */
  protected $databox_field;
  /**
   *
   * @var record_adapter 
   */
  protected $record;

  /**
   *
   * @param databox_field $databox_field
   * @param record_adapter $record
   * @param type $id
   * @return \caption_Field_Value 
   */
  public function __construct(databox_field $databox_field, record_adapter $record, $id)
  {
    $this->id = (int) $id;
    $this->databox_field = $databox_field;
    $this->record = $record;

    $connbas = $databox_field->get_databox()->get_connection();

    $sql = 'SELECT record_id, value FROM metadatas WHERE id = :id';

    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':id' => $id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $this->value = $row ? $row['value'] : null;

    return $this;
  }
  public function getId() {
      return $this->id;
  }

  public function getValue() {
      return $this->value;
  }

  public function getDatabox_field() {
      return $this->databox_field;
  }

  public function getRecord() {
      return $this->record;
  }

    public function delete()
  {
    $connbas = $this->databox_field->get_connection();

    $sql = 'DELETE FROM metadatas WHERE id = :id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':id' => $this->id));
    $stmt->closeCursor();
    $this->delete_data_from_cache();

    $sbas_id = $this->record->get_sbas_id();
    $this->record->get_caption()->delete_data_from_cache();

    try
    {
      $registry = registry::get_instance();
      $sphinx_rt = sphinxrt::get_instance($registry);

      $sbas_params = phrasea::sbas_params();

      if (isset($sbas_params[$sbas_id]))
      {
        $params = $sbas_params[$sbas_id];
        $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
        $sphinx_rt->delete(array("metadatas" . $sbas_crc, "metadatas" . $sbas_crc . "_stemmed_fr", "metadatas" . $sbas_crc . "_stemmed_en"), "metas_realtime" . $sbas_crc, $this->id);
        $sphinx_rt->delete(array("documents" . $sbas_crc, "documents" . $sbas_crc . "_stemmed_fr", "documents" . $sbas_crc . "_stemmed_en"), "docs_realtime" . $sbas_crc, $this->record->get_record_id());
      }
    }
    catch (Exception $e)
    {
      unset($e);
    }

    return $this;
  }

  public function set_value($value)
  {
    $sbas_id = $this->databox_field->get_databox()->get_sbas_id();
    $connbas = $this->databox_field->get_connection();

    $params = array(
        ':meta_id' => $this->id
        , ':value' => $value
    );

    $sql_up = 'UPDATE metadatas SET value = :value WHERE id = :meta_id';
    $stmt_up = $connbas->prepare($sql_up);
    $stmt_up->execute($params);
    $stmt_up->closeCursor();

    try
    {
      $registry = registry::get_instance();
      $sphinx_rt = sphinxrt::get_instance($registry);

      $sbas_params = phrasea::sbas_params();

      if (isset($sbas_params[$sbas_id]))
      {
        $params = $sbas_params[$sbas_id];
        $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
        $sphinx_rt->delete(array("metadatas" . $sbas_crc, "metadatas" . $sbas_crc . "_stemmed_fr", "metadatas" . $sbas_crc . "_stemmed_en"), "", $this->id);
        $sphinx_rt->delete(array("documents" . $sbas_crc, "documents" . $sbas_crc . "_stemmed_fr", "documents" . $sbas_crc . "_stemmed_en"), "", $this->record->get_record_id());
      }
    }
    catch (Exception $e)
    {
      
    }

    $this->update_cache_value($value);

    return $this;
  }

  /**
   *
   * @param array $value
   * @return caption_field
   */
  public function update_cache_value($value)
  {
//    $this->delete_data_from_cache();
    $this->record->get_caption()->delete_data_from_cache();
    $sbas_id = $this->databox_field->get_databox()->get_sbas_id();
    try
    {
      $registry = registry::get_instance();

      $sbas_params = phrasea::sbas_params();

      if (isset($sbas_params[$sbas_id]))
      {
        $params = $sbas_params[$sbas_id];
        $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));

        $sphinx_rt = sphinxrt::get_instance($registry);
        $sphinx_rt->replace_in_metas(
                "metas_realtime" . $sbas_crc, $this->id, $this->databox_field->get_id(), $this->record->get_record_id(), $sbas_id, phrasea::collFromBas($this->record->get_base_id()), ($this->record->is_grouping() ? '1' : '0'), $this->record->get_type(), $value, $this->record->get_creation_date()
        );

        $all_datas = array();
        foreach ($this->record->get_caption()->get_fields() as $field)
        {
          if (!$field->is_indexable())
            continue;
          $all_datas[] = $field->get_value(true);
        }
        $all_datas = implode(' ', $all_datas);

        $sphinx_rt->replace_in_documents(
                "docs_realtime" . $sbas_crc, //$this->id,
                $this->record->get_record_id(), $all_datas, $sbas_id, phrasea::collFromBas($this->record->get_base_id()), ($this->record->is_grouping() ? '1' : '0'), $this->record->get_type(), $this->record->get_creation_date()
        );
      }
    }
    catch (Exception $e)
    {
      unset($e);
    }

    return $this;
  }

  public static function create(databox_field &$databox_field, record_Interface $record, $value)
  {

    $sbas_id = $databox_field->get_databox()->get_sbas_id();
    $connbas = $databox_field->get_connection();
    
    $sql_ins = 'INSERT INTO metadatas (id, record_id, meta_struct_id, value)
            VALUES
            (null, :record_id, :field, :value)';
    $stmt_ins = $connbas->prepare($sql_ins);
    $stmt_ins->execute(
            array(
                ':record_id' => $record->get_record_id(),
                ':field' => $databox_field->get_id(),
                ':value' => $value
            )
    );
    $stmt_ins->closeCursor();
    $meta_id = $connbas->lastInsertId();

    $caption_field_value = new self($databox_field, $record, $meta_id);
    $caption_field_value->update_cache_value($value);

//    $record->get_caption()->delete_data_from_cache();

    return $caption_field_value;
  }
}