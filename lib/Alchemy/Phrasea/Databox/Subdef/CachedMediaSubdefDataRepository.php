<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Subdef;

use Alchemy\Phrasea\Cache\MultiAdapter;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\MultiGetCache;
use Doctrine\Common\Cache\MultiPutCache;

class CachedMediaSubdefDataRepository implements MediaSubdefDataRepository
{
    /**
     * @var MediaSubdefDataRepository
     */
    private $decorated;

    /**
     * @var Cache|MultiGetCache|MultiPutCache
     */
    private $cache;

    /**
     * @var string
     */
    private $baseKey;

    /**
     * @var int
     */
    private $lifeTime = 0;

    /**
     * @param MediaSubdefDataRepository $decorated
     * @param Cache $cache
     * @param string $baseKey
     */
    public function __construct(MediaSubdefDataRepository $decorated, Cache $cache, $baseKey)
    {
        $this->decorated = $decorated;
        $this->cache = $cache instanceof MultiGetCache && $cache instanceof MultiPutCache
            ? $cache
            : new MultiAdapter($cache);
        $this->baseKey = $baseKey;
    }

    /**
     * @return int
     */
    public function getLifeTime()
    {
        return $this->lifeTime;
    }

    /**
     * @param int $lifeTime
     */
    public function setLifeTime($lifeTime)
    {
        $this->lifeTime = (int)$lifeTime;
    }

    /**
     * @param int[] $recordIds
     * @param string[]|null $names
     * @return array
     */
    public function findByRecordIdsAndNames(array $recordIds, array $names = null)
    {
        $keys = $this->computeKeys($recordIds, $names);

        if ($keys) {
            $data = $this->cache->fetchMultiple($keys);

            if (count($keys) === count($data)) {
                return $this->filterNonNull($data);
            }
        }

        return $this->fetchAndSave($recordIds, $names, $keys);
    }

    /**
     * @param array $data
     * @return array
     */
    private function filterNonNull(array $data)
    {
        return array_values(array_filter($data, function ($value) {
            return null !== $value;
        }));
    }

    public function delete(array $subdefIds)
    {
        $deleted = $this->decorated->delete($subdefIds);

        $keys = array_map([$this, 'dataToKey'], $subdefIds);

        $this->cache->saveMultiple(array_fill_keys($keys, null), $this->lifeTime);

        return $deleted;
    }

    public function save(array $data)
    {
        $this->decorated->save($data);

        // all saved keys are now stalled. decorated repository could modify values on store (update time for example)
        $recordIds = [];

        foreach ($data as $item) {
            $recordIds[] = $item['record_id'];
        }

        $keys = array_merge(array_map([$this, 'dataToKey'], $data), $this->generateAllCacheKeys($recordIds));

        array_walk($keys, [$this->cache, 'delete']);
    }

    /**
     * @param array $data
     * @return string
     */
    private function dataToKey(array $data)
    {
        return $this->getCacheKey($data['record_id'], $data['name']);
    }

    /**
     * @param int $recordId
     * @param string|null $name
     * @return string
     */
    private function getCacheKey($recordId, $name = null)
    {
        return $this->baseKey . 'media_subdef' . json_encode([(int)$recordId, $name]);
    }

    /**
     * @param int[] $recordIds
     * @param string[] $names
     * @return string[]
     */
    private function generateCacheKeys(array $recordIds, array $names)
    {
        $names = array_unique($names);
        $namesCount = count($names);

        $keys = array_map(function ($recordId) use ($namesCount, $names) {
            return array_map([$this, 'getCacheKey'], array_fill(0, $namesCount, $recordId), $names);
        }, array_unique($recordIds));

        return $keys ? call_user_func_array('array_merge', $keys) : [];
    }

    /**
     * @param int[] $recordIds
     * @return string[]
     */
    private function generateAllCacheKeys(array $recordIds)
    {
        $recordIds = array_unique($recordIds);

        return array_map([$this, 'getCacheKey'], $recordIds, array_fill(0, count($recordIds), null));
    }

    /**
     * @param int[] $recordIds
     * @param string[]|null $names
     * @param string[] $keys Known keys supposed to be fetched
     * @return array
     */
    private function fetchAndSave(array $recordIds, array $names = null, array $keys = [])
    {
        $retrieved = $this->decorated->findByRecordIdsAndNames($recordIds, $names);

        $data = $this->normalizeRetrievedData($retrieved, $keys);

        $toCache = null === $names ? $this->appendCacheExtraData($data, $retrieved, $recordIds) : $data;

        $this->cache->saveMultiple($toCache, $this->lifeTime);

        return $this->filterNonNull($data);
    }

    /**
     * @param int[] $recordIds
     * @param string[]|null $names
     * @return string[]
     */
    private function computeKeys(array $recordIds, array $names = null)
    {
        if (!$recordIds) {
            return [];
        } elseif (null !== $names) {
            return $this->generateCacheKeys($recordIds, $names);
        }

        $keys = $this->generateAllCacheKeys($recordIds);
        $data = $this->cache->fetchMultiple($keys);

        return count($keys) === count($data) ? call_user_func_array('array_merge', $data) : [];
    }

    /**
     * @param array $retrieved
     * @param array $keys
     * @return array
     */
    private function normalizeRetrievedData(array $retrieved, array $keys)
    {
        $data = array_fill_keys($keys, null);

        foreach ($retrieved as $item) {
            $data[$this->dataToKey($item)] = $item;
        }

        return $data;
    }

    /**
     * @param array $data
     * @param array $retrieved
     * @param array $recordIds
     * @return array
     */
    private function appendCacheExtraData(array $data, array $retrieved, array $recordIds)
    {
        $extra = array_fill_keys($this->generateAllCacheKeys($recordIds), []);

        foreach ($retrieved as $item) {
            $extra[$this->getCacheKey($item['record_id'])][] = $this->getCacheKey($item['record_id'], $item['name']);
        }

        return array_merge($data, $extra);
    }
}
