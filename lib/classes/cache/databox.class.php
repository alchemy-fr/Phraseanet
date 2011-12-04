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
 * @package     cache
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class cache_databox
{

  /**
   *
   * @var cache_databox
   */
  private static $_instance = false;
  /**
   *
   * @var cache
   */
  protected $_c_obj = false;

  /**
   *
   * @return cache_databox
   */
  function __construct()
  {
    $this->_c_obj = cache_adapter::getInstance(registry::get_instance());

    return $this;
  }

  /**
   * @return cache_databox
   */
  public static function getInstance()
  {
    if (!(self::$_instance instanceof self))
      self::$_instance = new self();

    return self::$_instance;
  }

  /**
   *
   * @param string $type
   * @param string $what
   * @return boolean
   */
  public function get($type, $what)
  {
    return $this->_c_obj->get('_databox_' . $type . '_' . $what);
  }

  /**
   *
   * @param string $type
   * @param string $what
   * @param mixed content $bin
   * @return boolean
   */
  public function set($type, $what, $bin)
  {
    return $this->_c_obj->set('_databox_' . $type . '_' . $what, $bin);
  }

  /**
   *
   * @param string $type
   * @param string $what
   * @return boolean
   */
  public function delete($type, $what)
  {
    return $this->_c_obj->delete('_databox_' . $type . '_' . $what);
  }

  /**
   *
   * @param int $sbas_id
   * @return cache_databox
   */
  function refresh($sbas_id)
  {
    $date = new DateTime('-30 seconds');

    $registry = registry::get_instance();

    $cache_appbox = cache_appbox::getInstance();
    $last_update = $cache_appbox->get('memcached_update');
    if ($last_update)
      $last_update = new DateTime($last_update);
    else
      $last_update = new DateTime('-10 years');

    if ($date <= $last_update || !$cache_appbox->is_ok())

      return $this;

    $connsbas = connection::getInstance($sbas_id);

    if (!$connsbas)

      return $this;

    $sql = 'SELECT type, value FROM memcached
      WHERE site_id="' . $connsbas->escape_string($registry->get('GV_ServerName')) . '"';

    if ($rs = $connsbas->query($sql))
    {
      $cache_thumbnail = cache_thumbnail::getInstance();
      $cache_preview = cache_preview::getInstance();
      while ($row = $connsbas->fetch_assoc($rs))
      {
        switch ($row['type'])
        {
          case 'record':
            $cache_thumbnail->delete($sbas_id, $row['value'], false);
            $cache_preview->delete($sbas_id, $row['value'], false);
            $sql = 'DELETE FROM memcached
              WHERE site_id="' . $connsbas->escape_string($registry->get('GV_ServerName')) . '"
              AND type="record" AND value="' . $row['value'] . '"';
            $connsbas->query($sql);
            break;
          case 'structure':
            $cache_appbox->delete('list_bases');
            $sql = 'DELETE FROM memcached
              WHERE site_id="' . $connsbas->escape_string($registry->get('GV_ServerName')) . '"
              AND type="structure" AND value="' . $row['value'] . '"';
            $connsbas->query($sql);
            break;
        }
      }
      $connsbas->free_result($rs);
    }

    $date = new DateTime();
    $now = phraseadate::format_mysql($date);
    $cache_appbox->set('memcached_update', $now);

    $conn = connection::getInstance();
    $sql = 'UPDATE sitepreff
            SET memcached_update="' . $conn->escape_string($now) . '"';
    $conn->query($sql);

    return $this;
  }

  /**
   *
   * @param int $sbas_id
   * @param string $type
   * @param mixed content $value
   * @return Void
   */
  function update($sbas_id, $type, $value='')
  {

    $connbas = connection::getPDOConnection($sbas_id);

    $registry = registry::get_instance();

    $sql = 'SELECT distinct site_id as site_id
            FROM clients
            WHERE site_id != :site_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':site_id' => $registry->get('GV_ServerName')));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $sql = 'REPLACE INTO memcached (site_id, type, value)
            VALUES (:site_id, :type, :value)';
    $stmt = $connbas->prepare($sql);
    foreach ($rs as $row)
    {
      $stmt->execute(array(':site_id' => $row['site_id'], ':type' => $type, ':value' => $value));
    }
    $stmt->closeCursor();

    return;
  }

}
