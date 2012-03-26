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
class patch_3604 implements patchInterface
{

  /**
   *
   * @var string
   */
  private $release = '3.6.0a2';

  /**
   *
   * @var Array
   */
  private $concern = array(base::DATA_BOX);

  /**
   *
   * @return string
   */
  function get_release()
  {
    return $this->release;
  }

  public function require_all_upgrades()
  {
    return true;
  }

  /**
   *
   * @return Array
   */
  function concern()
  {
    return $this->concern;
  }

  function apply(base &$databox)
  {
    $sql = 'SELECT m . *
      FROM metadatas_structure s, metadatas m
      WHERE m.meta_struct_id = s.id
      AND s.multi = "1"';

    $stmt     = $databox->get_connection()->prepare($sql);
    $stmt->execute();
    $rowCount = $stmt->rowCount();
    $stmt->closeCursor();

    $n       = 0;
    $perPage = 1000;

    while ($n < $rowCount)
    {

      $sql = 'SELECT m . *
      FROM metadatas_structure s, metadatas m
      WHERE m.meta_struct_id = s.id
      AND s.multi = "1" LIMIT ' . $n . ', ' . $perPage;

      $stmt = $databox->get_connection()->prepare($sql);
      $stmt->execute();
      $rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      $databox->get_connection()->beginTransaction();

      $sql  = 'INSERT INTO metadatas(id, record_id, meta_struct_id, value)
                VALUES (null, :record_id, :meta_struct_id, :value)';
      $stmt = $databox->get_connection()->prepare($sql);

      $databox_fields = array();

      foreach ($rs as $row)
      {
        $meta_struct_id = $row['meta_struct_id'];

        if ( ! isset($databox_fields[$meta_struct_id]))
        {
          $databox_fields[$meta_struct_id] = \databox_field::get_instance($databox, $meta_struct_id);
        }

        $values = \caption_field::get_multi_values($row['value'], $databox_fields[$meta_struct_id]->get_separator());

        foreach ($values as $value)
        {
          $params = array(
            ':record_id'      => $row['record_id'],
            ':meta_struct_id' => $row['meta_struct_id'],
            ':value'          => $value,
          );
          $stmt->execute($params);
        }
      }

      $stmt->closeCursor();


      $sql  = 'DELETE FROM metadatas WHERE id = :id';
      $stmt = $databox->get_connection()->prepare($sql);

      foreach ($rs as $row)
      {
        $params = array(':id' => $row['id']);
        $stmt->execute($params);
      }

      $stmt->closeCursor();

      $databox->get_connection()->commit();

      $n+= $perPage;
    }

    return true;
  }

}
