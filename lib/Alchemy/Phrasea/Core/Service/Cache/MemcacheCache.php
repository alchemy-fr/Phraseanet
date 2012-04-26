<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Cache;

use Alchemy\Phrasea\Core,
    Alchemy\Phrasea\Core\Service,
    Alchemy\Phrasea\Core\Service\ServiceAbstract,
    Alchemy\Phrasea\Core\Service\ServiceInterface,
    Alchemy\Phrasea\Cache as CacheDriver;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MemcacheCache extends ServiceAbstract
{
    const DEFAULT_HOST = "localhost";
    const DEFAULT_PORT = "11211";

    protected $cache;
    protected $host;
    protected $port;

    protected function init()
    {
        $options = $this->getOptions();

        $this->host = isset($options["host"]) ? $options["host"] : self::DEFAULT_HOST;

        $this->port = isset($options["port"]) ? $options["port"] : self::DEFAULT_PORT;
    }

    public function getDriver()
    {
        if ( ! extension_loaded('memcache')) {
            throw new \Exception('The Memcache cache requires the Memcache extension.');
        }

        if ( ! $this->cache) {
            $memcache = new \Memcache();

            $memcache->addServer($this->host, $this->port);

            $key = sprintf("%s:%s", $this->host, $this->port);

            $stats = @$memcache->getExtendedStats();

            if (isset($stats[$key])) {
                $this->cache = new CacheDriver\MemcacheCache();
                $this->cache->setMemcache($memcache);

                $this->cache->setNamespace(md5(realpath(__DIR__ . '/../../../../../../')));
            } else {
                throw new \Exception(sprintf("Memcache instance with host '%s' and port '%s' is not reachable", $this->host, $this->port));
            }
        }

        return $this->cache;
    }

    public function getType()
    {
        return 'memcache';
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }
}

