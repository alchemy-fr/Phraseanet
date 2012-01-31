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
class registry implements registryInterface
{

  /**
   *
   * @var cache_opcode_adapter
   */
  protected $cache;

  /**
   *
   * @var registry
   */
  protected static $_instance;

  const TYPE_BOOLEAN = 'boolean';
  const TYPE_ARRAY = 'array';
  const TYPE_ENUM_MULTI = 'enum_multi';
  const TYPE_INTEGER = 'integer';
  const TYPE_STRING = 'string';

  /**
   *
   * @return registry
   */
  public static function get_instance()
  {
    $prefix = crc32(__DIR__);
    if (!self::$_instance instanceof self)
      self::$_instance = new self(new cache_opcode_adapter($prefix));

    return self::$_instance;
  }

  /**
   *
   * @param cache_opcode_interface $cache
   * @return registry
   */
  protected function __construct(cache_opcode_interface $cache)
  {
    $this->cache = $cache;

    $handler = new \Alchemy\Phrasea\Core\Configuration\Handler(
                    new \Alchemy\Phrasea\Core\Configuration\Application(),
                    new \Alchemy\Phrasea\Core\Configuration\Parser\Yaml()
    );
    $configuration = new \Alchemy\Phrasea\Core\Configuration($handler);

    $this->cache->set('GV_RootPath', dirname(dirname(__DIR__)) . '/');
    if ($configuration->isInstalled())
    {
      $this->cache->set('GV_ServerName', $configuration->getPhraseanet()->get('servername'));
      $this->cache->set('GV_debug', $configuration->isDebug());
      $this->cache->set('GV_maintenance', $configuration->isMaintained());
    }

    return $this;
  }

  /**
   *
   * @return registry
   */
  protected function load()
  {
    if ($this->cache->get('registry_loaded') !== true)
    {
      $rs = array();
      $loaded = false;
      try
      {
        $conn = connection::getPDOConnection();
        $sql = 'SELECT `key`, `value`, `type` FROM registry';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $loaded = true;
      }
      catch (Exception $e)
      {

      }
      foreach ($rs as $row)
      {
        switch ($row['type'])
        {
          case self::TYPE_BOOLEAN:
            $value = !!$row['value'];
            break;
          case self::TYPE_INTEGER:
            $value = (int) $row['value'];
            break;
          case self::TYPE_ENUM_MULTI:
          case self::TYPE_ARRAY:
            $value = unserialize($row['value']);
            break;
          case self::TYPE_STRING:
          default:
            $value = $row['value'];
            break;
        }

        $this->cache->set($row['key'], $value);
      }
      if ($loaded === true)
        $this->cache->set('registry_loaded', true);
    }


    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function get($key, $defaultvalue = null)
  {
    if (!$this->cache->is_set($key))
      $this->load();

    if (!$this->cache->is_set($key) && !is_null($defaultvalue))

      return $defaultvalue;
    else

      return $this->cache->get($key);
  }

  /**
   *
   * @param string $key
   * @param mixed $value
   * @return registry
   */
  public function set($key, $value, $type)
  {
    $this->load();
    $delete_cache = false;
    if ($key === 'GV_cache_server_type')
    {
      $current_cache = $this->get('GV_cache_server_type');
      if ($current_cache !== $value)
        $delete_cache = true;
    }


    switch ($type)
    {
      case self::TYPE_ARRAY:
      case self::TYPE_ENUM_MULTI:
        $sql_value = serialize($value);
        $value = (array) $value;
        break;
      case self::TYPE_STRING;
      default:
        $sql_value = (string) $value;
        $value = (string) $value;
        break;
      case self::TYPE_BOOLEAN:
        $sql_value = $value ? '1' : '0';
        $value = !!$value;
        break;
      case self::TYPE_INTEGER:
        $sql_value = (int) $value;
        $value = (int) $value;
        break;
    }

    $conn = connection::getPDOConnection();

    $sql = 'REPLACE INTO registry (`id`, `key`, `value`, `type`)
            VALUES (null, :key, :value, :type)';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':key' => $key, ':value' => $sql_value, ':type' => $type));
    $stmt->closeCursor();

    $this->cache->set($key, $value);

    if ($delete_cache === true)
    {
      $cache = cache_adapter::get_instance($this);
      $cache->flush();
    }

    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function is_set($key)
  {
    $this->load();

    return $this->cache->is_set($key);
  }

  /**
   *
   * @param string $key
   * @return registry
   */
  public function un_set($key)
  {
    $this->load();
    $conn = connection::getPDOConnection();

    $sql = 'DELETE FROM registry WHERE `key` = :key';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':key' => $key));
    $stmt->closeCursor();

    $this->cache->un_set($key);

    return $this;
  }

}
