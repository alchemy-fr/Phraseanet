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
class sphinxrt
{

  protected static $_instance;
  protected static $_failure = false;
  protected $connection;

  protected function __construct(registry $registry)
  {
    try
    {
      $dsn = sprintf('mysql:host=%s;port=%s;', $registry->get('GV_sphinx_rt_host'), $registry->get('GV_sphinx_rt_port'));
      $this->connection = @new PDO($dsn);
    }
    catch (Exception $e)
    {
      self::$_failure = true;
      throw new Exception('Unable to connect to sphinx rt');
    }

    return $this;
  }

  /**
   *
   * @param registry $registry
   * @return sphinxrt
   */
  public static function get_instance(registry $registry, $retry_on_failure = false)
  {
    if (!$retry_on_failure && self::$_failure === true)
    {
      throw new Exception('Unable to connect to sphinx rt, try set retry_on_failure true');
    }
    if (!self::$_instance instanceof self)
    {
      self::$_instance = new self($registry);
    }

    return self::$_instance;
  }

  /**
   * Delete an index
   *
   * @param array $index_ids
   * @param <type> $rt_id
   * @param <type> $meta_id
   * @return sphinxrt
   */
  public function delete(Array $index_ids, $rt_id, $id)
  {
    $registry = registry::get_instance();
    require_once $registry->get('GV_RootPath') . 'lib/vendor/sphinx/sphinxapi.php';

    $cl = new SphinxClient();

    $cl->SetServer($registry->get('GV_sphinx_host'), (int) $registry->get('GV_sphinx_port'));
    $cl->SetConnectTimeout(1);

    foreach ($index_ids as $index_id)
      $cl->UpdateAttributes($index_id, array("deleted"), array($id => array(1)));

    if ($rt_id)
    {
      $this->connection->beginTransaction();
      $sql  = "DELETE FROM " . $rt_id . " WHERE id = " . (int) $id . "";
      $stmt = $this->connection->prepare($sql);
      $stmt->execute();
      $stmt->closeCursor();
      $this->connection->commit();
    }

    return $this;
  }

  public function update_status(Array $index_ids, $sbas_id, $record_id, $status)
  {
    $registry = registry::get_instance();
    require_once $registry->get('GV_RootPath') . 'lib/vendor/sphinx/sphinxapi.php';

    $cl = new SphinxClient();

    if ($cl->Status() === false)

      return $this;

    $cl->SetServer($registry->get('GV_sphinx_host'), (int) $registry->get('GV_sphinx_port'));
    $cl->SetConnectTimeout(1);


    $status   = strrev($status);
    $new_stat = array();
    for ($i = 4; $i < strlen($status); $i++)
    {
      if (substr($status, $i, 1) == '1')
        $new_stat[] = crc32($sbas_id . '_' . $i);
    }

    foreach ($index_ids as $index_id)
    {
      $cl->UpdateAttributes($index_id, array("status"), array($record_id => array($new_stat)), true);
    }

    return $this;
  }

  public function replace_in_metas($rt_id, $meta_id, $tag_id, $record_id, $sbas_id, $coll_id, $grouping, $type, $content, DateTime $created)
  {
    $crc_sbas_tag    = crc32($sbas_id . '_' . $tag_id);
    $crc_sbas_coll   = crc32($sbas_id . '_' . $coll_id);
    $crc_sbas_record = crc32($sbas_id . '_' . $record_id);
    $crc_type        = crc32($type);

    $this->connection->beginTransaction();

    $sql  = "REPLACE INTO " . $rt_id . " VALUES (
        '" . (int) $meta_id . "'
        ,'" . str_replace("'", "\'", $content) . "'
        ,'" . (int) $tag_id . "'
        ," . (int) $record_id . "
        ," . (int) $sbas_id . "
        ," . (int) $coll_id . "
        ," . (int) $grouping . "
        ," . (int) $crc_sbas_tag . "
        ," . (int) $crc_sbas_coll . "
        ," . (int) $crc_sbas_record . "
        ," . (int) $crc_type . "
        ,0
        ," . (int) $created->format('U') . " )";
    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $stmt->closeCursor();

    $this->connection->commit();

    return $this;
  }

  public function replace_in_documents($rt_id, $record_id, $value, $sbas_id, $coll_id, $grouping, $type, DateTime $created)
  {
    $crc_sbas_coll   = crc32($sbas_id . '_' . $coll_id);
    $crc_sbas_record = crc32($sbas_id . '_' . $record_id);
    $crc_type        = crc32($type);

    $this->connection->beginTransaction();

    $sql = "REPLACE INTO " . $rt_id . " VALUES (
        '" . (int) $record_id . "'
        ,'" . str_replace("'", "\'", $value) . "'
        ," . (int) $record_id . "
        ," . (int) $sbas_id . "
        ," . (int) $coll_id . "
        ," . (int) $grouping . "
        ," . (int) $crc_sbas_coll . "
        ," . (int) $crc_sbas_record . "
        ," . (int) $crc_type . "
        ,0
        ," . (int) $created->format('U') . " )";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();

    $this->connection->commit();

    return $this;
  }

}
