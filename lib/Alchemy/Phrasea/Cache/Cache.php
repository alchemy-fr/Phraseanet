<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Doctrine\Common\Cache\Cache as DoctrineCache;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface Cache extends DoctrineCache
{
    /**
     * Name of the cache driver
     * @return string
     */
    public function getName();

    /**
     * Tell whether the caching system use a server or not
     * @return boolean
     */
    public function isServer();

    /**
     * Tell if the cache system is online
     * @return boolean
     */
    public function isOnline();

    /**
     * Get an entry from the cache.
     *
     * @param  string $key cache id The id of the cache entry to fetch.
     * @return string The cached data.
     * @return FALSE, if no cache entry exists for the given id.
     * @ throws Alchemy\Phrasea\Cache\Exception if provided key does not exist
     */
    public function get($key);

    /**
     * Delete multi cache entries
     *
     * @param  array                       $keys contains all keys to delete
     * @return Alchemy\Phrasea\Cache\Cache
     */
    public function deleteMulti(array $keys);
}
