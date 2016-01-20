<?php

namespace Alchemy\Phrasea\Core\Profiler;

use Alchemy\Phrasea\Cache\Cache as PhraseaCache;
use Alchemy\Phrasea\Cache\Exception;
use Doctrine\Common\Cache\Cache;

class TraceableCache implements Cache, PhraseaCache
{

    /**
     * @var PhraseaCache
     */
    private $cache;

    /**
     * @var string
     */
    private $namespace = '';

    /**
     * @var array
     */
    private $calls = [];

    private $summary = [
        'calls' => 0,
        'hits' => 0,
        'misses' => 0,
        'calls_by_type' => [],
        'calls_by_key' => [],
    ];

    /*s*
     * @param PhraseaCache $cache
     */
    public function __construct(PhraseaCache $cache)
    {
        $this->cache = $cache;
    }

    private function collect($type, $id, $hit = true, $result = null)
    {
        $this->summary['calls']++;
        $this->summary['hits'] += $hit ? 1 : 0;
        $this->summary['misses'] += $hit ? 0 : 1;

        if (! array_key_exists($type, $this->summary['calls_by_type'])) {
            $this->summary['calls_by_type'][$type] = 0;
        }

        $this->summary['calls_by_type'][$type]++;

        if (! array_key_exists($id, $this->summary['calls_by_key'])) {
            $this->summary['calls_by_key'][$id] = [
                'total' => 0,
                'reads'=> 0,
                'writes' => 0,
                'hits' => 0,
                'misses' => 0
            ];
        }

        if (! array_key_exists($type, $this->summary['calls_by_key'][$id])) {
            $this->summary['calls_by_key'][$id][$type] = 0;
        }
        $this->summary['calls_by_key'][$id]['hits'] += $hit ? 1 : 0;
        $this->summary['calls_by_key'][$id]['misses'] += $hit ? 0 : 1;

        $this->summary['calls_by_key'][$id]['reads'] += ($type == 'fetch' || $type == 'contains') ? 1 : 0;
        $this->summary['calls_by_key'][$id]['writes'] += ($type == 'fetch' || $type == 'contains') ? 0 : 1;

        $this->summary['calls_by_key'][$id]['total']++;

        $this->calls[] = [
            'type' => $type,
            'key'  => $id,
            'result' => $result,
            'hit'  => (bool) $hit
        ];

    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getCalls()
    {
        return $this->calls;
    }

    /**
     * @return array
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetch($id)
    {
        try {
            $value = $this->cache->fetch($id);
        }
        catch (\Exception $ex) {
            $value = false;
        }

        $this->collect('fetch', $id, $value != false, $value);

        return $value;
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return bool TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    public function contains($id)
    {
        $this->collect('contains', $id);

        return $this->cache->contains($id);
    }

    /**
     * Puts data into the cache.
     *
     * If a cache entry with the given id already exists, its data will be replaced.
     *
     * @param string $id The cache id.
     * @param mixed $data The cache entry/data.
     * @param int $lifeTime The lifetime in number of seconds for this cache entry.
     *                         If zero (the default), the entry never expires (although it may be deleted from the cache
     *                         to make place for other entries).
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $this->collect('save', $id);

        return $this->cache->save($id, $data, $lifeTime);
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     *              Deleting a non-existing entry is considered successful.
     */
    public function delete($id)
    {
        $this->collect('delete', $id);

        return $this->cache->delete($id);
    }

    /**
     * Retrieves cached information from the data store.
     *
     * The server's statistics array has the following values:
     *
     * - <b>hits</b>
     * Number of keys that have been requested and found present.
     *
     * - <b>misses</b>
     * Number of items that have been requested and not found.
     *
     * - <b>uptime</b>
     * Time that the server is running.
     *
     * - <b>memory_usage</b>
     * Memory used by this server to store items.
     *
     * - <b>memory_available</b>
     * Memory allowed to use for storage.
     *
     * @since 2.2
     *
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    public function getStats()
    {
        return $this->cache->getStats();
    }

    /**
     * Sets the namespace
     *
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        $this->cache->setNamespace($namespace);
    }

    /**
     * Flushes all data contained in the adapter
     */
    public function flushAll()
    {
        $this->collect('flush-all', null);
        $this->cache->flushAll();
    }

    /**
     * Name of the cache driver
     * @return string
     */
    public function getName()
    {
        return 'traceable-' . $this->cache->getName();
    }

    /**
     * Tell whether the caching system use a server or not
     * @return boolean
     */
    public function isServer()
    {
        return $this->cache->isServer();
    }

    /**
     * Tell if the cache system is online
     * @return boolean
     */
    public function isOnline()
    {
        return $this->cache->isOnline();
    }

    /**
     * Get an entry from the cache.
     *
     * @param string $key cache id The id of the cache entry to fetch.
     *
     * @return string The cached data.
     * @return FALSE, if no cache entry exists for the given id.
     *
     * @throws Exception if provided key does not exist
     */
    public function get($key)
    {
        return $this->fetch($key);
    }

    /**
     * Delete multi cache entries
     *
     * @param  array $keys contains all keys to delete
     * @return PhraseaCache
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }
}
