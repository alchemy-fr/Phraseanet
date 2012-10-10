<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Cache\ArrayCache;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class registry implements registryInterface
{
    /**
     *
     * @var \Alchemy\Phrasea\Cache\Cache
     */
    protected $cache;

    protected $app;

    const TYPE_BOOLEAN = 'boolean';
    const TYPE_ENUM_MULTI = 'enum_multi';
    const TYPE_INTEGER = 'integer';
    const TYPE_ENUM = 'enum';
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_TIMEZONE = 'timezone';
    const TYPE_BINARY = 'binary';

    /**
     *
     * @param  \Alchemy\Phrasea\Cache\Cache $cache
     * @return registry
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->cache = new ArrayCache();

        $this->cache->save('GV_RootPath', dirname(dirname(__DIR__)) . '/');
        if ($app['phraseanet.configuration-tester']->isInstalled()) {
            $this->cache->save('GV_ServerName', $app['phraseanet.configuration']->getPhraseanet()->get('servername'));
            $this->cache->save('GV_debug', $app['phraseanet.configuration']->isDebug());
            $this->cache->save('GV_maintenance', $app['phraseanet.configuration']->isMaintained());

            $config = $app['phraseanet.configuration']->getConfigurations();

            if (isset($config['key'])) {
                $this->cache->save('GV_sit', $config['key']);
            }

            $binaries = $app['phraseanet.configuration']->getBinaries();
            if (isset($binaries['binaries'])) {
                foreach ($binaries['binaries'] as $name => $path) {
                    $this->cache->save($name, $path);
                }
            }
        }

        return $this;
    }

    /**
     *
     * @return registry
     */
    protected function load()
    {
        if ($this->cache->fetch('registry_loaded') !== true) {
            $rs = array();
            $loaded = false;
            try {
                $conn = connection::getPDOConnection($this->app);
                $sql = 'SELECT `key`, `value`, `type` FROM registry';
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                $loaded = true;
            } catch (Exception $e) {

            }
            foreach ($rs as $row) {
                if (in_array($row['key'], array('GV_ServerName', 'GV_sit', 'GV_debug', 'GV_maintenance'))) {
                    continue;
                }

                switch ($row['type']) {
                    case self::TYPE_BOOLEAN:
                        $value = !!$row['value'];
                        break;
                    case self::TYPE_INTEGER:
                        $value = (int) $row['value'];
                        break;
                    case self::TYPE_ENUM_MULTI:
                        $value = unserialize($row['value']);
                        break;
                    case self::TYPE_BINARY:
                        continue;
                        break;
                    case self::TYPE_STRING:
                    case self::TYPE_ENUM:
                    case self::TYPE_TIMEZONE:
                    case self::TYPE_TEXT:
                    default:
                        $value = $row['value'];
                        break;
                }

                $this->cache->save($row['key'], $value);
            }
            if ($loaded === true)
                $this->cache->save('registry_loaded', true);
        }

        return $this;
    }

    /**
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key, $defaultvalue = null)
    {
        if (!$this->cache->contains($key))
            $this->load();

        if (!$this->cache->contains($key) && !is_null($defaultvalue)) {
            return $defaultvalue;
        }

        return $this->cache->fetch($key);
    }

    /**
     *
     * @param  string   $key
     * @param  mixed    $value
     * @return registry
     */
    public function set($key, $value, $type)
    {
        $this->load();

        switch ($type) {
            case self::TYPE_ENUM_MULTI:
                $sql_value = serialize($value);
                $value = (array) $value;
                break;
            case self::TYPE_STRING;
            case self::TYPE_ENUM:
            case self::TYPE_TIMEZONE:
            case self::TYPE_TEXT:
            case self::TYPE_BINARY:
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

        if ($type == self::TYPE_BINARY) {
            return $this;
        }

        $conn = connection::getPDOConnection($this->app);

        $sql = 'REPLACE INTO registry (`id`, `key`, `value`, `type`)
            VALUES (null, :key, :value, :type)';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':key'   => $key, ':value' => $sql_value, ':type'  => $type));
        $stmt->closeCursor();

        $this->cache->save($key, $value);

        return $this;
    }

    /**
     *
     * @param  string $key
     * @return mixed
     */
    public function is_set($key)
    {
        $this->load();

        return $this->cache->contains($key);
    }

    /**
     *
     * @param  string   $key
     * @return registry
     */
    public function un_set($key)
    {
        $this->load();
        $conn = connection::getPDOConnection($this->app);

        $sql = 'DELETE FROM registry WHERE `key` = :key';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':key' => $key));
        $stmt->closeCursor();

        $this->cache->delete($key);

        return $this;
    }
}
